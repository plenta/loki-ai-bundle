import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    loadingClass = 'loading';

    run() {
        this.element.classList.add(this.loadingClass);

        fetch(this.element.dataset.prefix + '/_loki/prompt/' + this.element.dataset.id + '/' + this.element.dataset.field + '/' + this.element.dataset.objectId).then(r => {
            if (r.ok) {
                return r.json();
            }

            this.buildDialog('<p>An error occurred while building the prompt.</p><p>Please check your API key. If the API key is correct and the error persists, please check your error logs and <a href="https://github.com/plenta/loki-ai-bundle/issues" target="_blank">create a ticket.</a></p>');
        })
            .then(r => {
            this.element.classList.remove(this.loadingClass);

            if (r.result) {
                let input = this.element.closest('.widget').querySelector('input[id]');
                let textarea = this.element.closest('.widget').querySelector('textarea[id]');
                let select = this.element.closest('.widget').querySelector('select[id]');

                if (input) {
                    input.value = r.result;
                } else if (textarea) {
                    textarea.innerText = r.result;

                    if (window.tinymce && window.tinymce.get(textarea.id)) {
                        window.tinymce.get(textarea.id).setContent(r.result);
                    }
                } else if (select) {
                    select.querySelectorAll('option[selected]').forEach(function (item) {
                        item.selected = false;
                    })

                    let option = select.querySelector('option[value="' + r.result + '"]');

                    if (option) {
                        option.selected = true;
                    }

                    if (select.classList.contains('tl_chosen')) {
                        select.dispatchEvent(new Event('input', {bubbles: true}));
                        const chosenContainer = select.parentElement.querySelector('.chzn-container');
                        if (chosenContainer) {
                            chosenContainer.remove(); // Remove Chosen's UI
                            new Chosen(select);
                        }
                    }
                }
            } else if (r.error) {
                this.buildDialog(r.error);
            }
        });
    }

    buildDialog(msg)
    {
        let dialog = document.createElement('dialog');
        dialog.innerHTML = msg;
        dialog.classList.add('loki-error');
        let button = document.createElement('button');
        button.innerHTML = 'Close';
        dialog.appendChild(button);
        document.body.appendChild(dialog);
        dialog.showModal();
        dialog.querySelector('button').addEventListener('click', () => {
            dialog.close();
            dialog.remove();
        });
    }
}