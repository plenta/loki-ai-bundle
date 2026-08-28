import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const siblings = this.element.parentElement.querySelectorAll('.loki-prompt-button');

        if (siblings.length > 1) {
            const button = this.element.querySelector('button[title]');

            if (button) {
                button.removeAttribute('title');
                button.removeAttribute('data-contao--tooltips-target');
            }
        }
    }
}