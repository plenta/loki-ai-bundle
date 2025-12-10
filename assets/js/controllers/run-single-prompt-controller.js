import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    loadingClass = 'loading';

    run() {
        this.element.classList.add(this.loadingClass);

        fetch(
            `${this.element.dataset.prefix}/_loki/prompt/${this.element.dataset.id}/${this.element.dataset.field}/${this.element.dataset.objectId}`,
        )
            .then((r) => {
                if (r.ok) {
                    return r.json();
                }

                this.buildDialog(
                    '<p>An error occurred while building the prompt.</p><p>Please check your API key. If the API key is correct and the error persists, please check your error logs and <a href="https://github.com/plenta/loki-ai-bundle/issues" target="_blank">create a ticket.</a></p>',
                );

                return [];
            })
            .then((r) => {
                this.element.classList.remove(this.loadingClass);

                if (r.result) {
                    const input = this.element.closest('.widget').querySelector('input[id]');
                    const textarea = this.element.closest('.widget').querySelector('textarea[id]');
                    const select = this.element.closest('.widget').querySelector('select[id]');

                    if (input) {
                        input.value = r.result;
                    } else if (textarea) {
                        textarea.innerText = r.result;

                        if (window.tinymce && window.tinymce.get(textarea.id)) {
                            window.tinymce.get(textarea.id).setContent(r.result);
                        }
                    } else if (select) {
                        select.querySelectorAll('option[selected]').forEach((item) => {
                            item.selected = false;
                        });

                        const option = select.querySelector(`option[value="${r.result}"]`);

                        if (option) {
                            option.selected = true;
                        }

                        if (select.classList.contains('tl_chosen')) {
                            select.dispatchEvent(new Event('input', { bubbles: true }));
                            const chosenContainer = select.parentElement.querySelector('.chzn-container');
                            if (chosenContainer) {
                                chosenContainer.remove(); // Remove Chosen's UI
                                /* eslint-disable-next-line no-undef */
                                new Chosen(select);
                            }
                        }
                    }
                } else if (r.error) {
                    this.buildDialog(r.error);
                }
            });
    }

    /* eslint-disable class-methods-use-this */
    buildDialog(msg) {
        const button = document.createElement('button');
        button.innerText = 'x';
        button.classList.add('close');
        button.type = 'button';

        const modalHeader = document.createElement('div');
        modalHeader.classList.add('simple-modal-header');
        modalHeader.innerHTML = '<h1>Loki AI - Error</h1>';
        modalHeader.appendChild(button);

        const dialog = document.createElement('dialog');
        dialog.classList.add('plenta-loki-error');
        dialog.classList.add('simple-modal');
        dialog.classList.add('hide-footer');

        dialog.appendChild(modalHeader);
        dialog.appendHTML(`<div class="simple-modal-body"><div class="contents">${msg}</div></div>`);
        document.body.appendChild(dialog);

        dialog.showModal();
        dialog.querySelector('button').addEventListener('click', () => {
            dialog.close();
            dialog.remove();
        });
    }
    /* eslint-enable class-methods-use-this */
}
