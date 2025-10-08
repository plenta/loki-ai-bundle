import "../scss/backend.scss";

document.addEventListener('DOMContentLoaded', function () {
    const loadingClass = 'loading';

    document.querySelectorAll('.loki-prompt-button button').forEach(function (item) {
        item.addEventListener('click', function () {
            item.classList.add(loadingClass);

            fetch(item.dataset.prefix + '/_loki/prompt/' + item.dataset.id + '/' + item.dataset.field + '/' + item.dataset.objectId).then(r => r.json()).then(r => {
                item.classList.remove(loadingClass);

                if (r.result) {
                    let input = item.closest('.widget').querySelector('input[id]');
                    let textarea = item.closest('.widget').querySelector('textarea[id]');
                    let select = item.closest('.widget').querySelector('select[id]');

                    if (input) {
                        input.value = r.result;
                    } else if (textarea) {
                        textarea.innerText = r.result;

                        if (tinymce && tinymce.get(textarea.id)) {
                            tinymce.get(textarea.id).setContent(r.result);
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
                            select.dispatchEvent(new Event('input', { bubbles: true }));
                            const chosenContainer = select.parentElement.querySelector('.chzn-container');
                            if (chosenContainer) {
                                chosenContainer.remove(); // Remove Chosen's UI
                                new Chosen(select);
                            }
                        }
                    }
                }
            })
        })
    })
})

document.addEventListener('DOMContentLoaded', async function () {

    let prompts = document.querySelector('.prompts.progress');

    if (prompts) {
        let fields = JSON.parse(window.loki_fields);
        let errorCount = parseInt(document.querySelector('.error-count').innerText);
        let errorMessages = document.querySelector('.error-messages');
        let errors = document.querySelector('.errors');
        let completed = parseInt(document.querySelector('.completed').innerText);
        let block = false;

        for (let key = 0; key < fields.length; key++) {
            let field = fields[key];
            let response = await fetch('/contao/_loki/execute/' + field.field + '/' + field.fieldName + '/' + field.id);
            let data = await response.json();

            if (data.error) {
                errors.classList.remove('invisible');
                errorCount += 1;
                document.querySelector('.error-count').innerText = errorCount;
                let p = document.createElement('p');
                p.innerText = data.error;
                p.classList.add('error-message');
                errorMessages.append(p);
            }

            completed += 1;
            document.querySelector('.completed').innerText = completed;
            block = false;

            if (completed === fields.length) {
                let status = document.querySelector('.status.pending');
                status.classList.remove('pending');
                status.classList.add('complete');
            }
        }
    }
})