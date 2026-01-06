import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['completed', 'errorCount', 'errorMessages', 'errors', 'status', 'warnings', 'warningCount'];

    async connect() {
        const fields = JSON.parse(window.loki_fields);
        let errorCount = parseInt(this.errorCountTarget.innerText, 10);
        const errorMessages = this.errorMessagesTarget;
        const errors = this.errorsTarget;
        let completed = parseInt(this.completedTarget.innerText, 10);
        let warningCount = parseInt(this.warningCountTarget.innerText, 10);

        /* eslint-disable no-await-in-loop */
        for (let key = 0; key < fields.length; key += 1) {
            const field = fields[key];
            const response = await fetch(`/contao/_loki/execute/${field.field}/${field.fieldName}/${field.id}`);
            const data = await response.json();

            if (data.error) {
                errors.classList.remove('invisible');
                errorCount += 1;
                this.errorCountTarget.innerText = errorCount;
                const p = document.createElement('p');
                p.innerText = data.error;
                p.classList.add('error-message');
                errorMessages.append(p);
            } else if (data.warning === 'empty') {
                this.warningsTarget.classList.remove('invisible');
                warningCount += 1;
                this.warningCountTarget.innerText = warningCount;
            }

            completed += 1;
            this.completedTarget.innerText = completed;

            if (completed === fields.length) {
                this.statusTarget.classList.remove('pending');
                this.statusTarget.classList.add('complete');
            }
        }
        /* eslint-enable no-await-in-loop */
    }
}
