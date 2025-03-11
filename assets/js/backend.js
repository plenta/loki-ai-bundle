document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.prompt-button button').forEach(function (item) {
        item.addEventListener('click', function () {
            fetch(item.dataset.prefix + '/_loki/prompt/' + item.dataset.id + '/' + item.dataset.field + '/' + item.dataset.objectId).then(r => r.json()).then(r => {
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