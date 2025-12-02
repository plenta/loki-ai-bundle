import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [ 'completed', 'errorCount', 'errorMessages', 'errors', 'status', 'warnings', 'warningCount' ];
    async connect() {

        let fields = JSON.parse(window.loki_fields);
        let errorCount = parseInt(this.errorCountTarget.innerText);
        let errorMessages = this.errorMessagesTarget;
        let errors = this.errorsTarget;
        let completed = parseInt(this.completedTarget.innerText);
        let block = false;
        let warningCount = parseInt(this.warningCountTarget.innerText);

        for (let key = 0; key < fields.length; key++) {
            let field = fields[key];
            let response = await fetch('/contao/_loki/execute/' + field.field + '/' + field.fieldName + '/' + field.id);
            let data = await response.json();

            if (data.error) {
                errors.classList.remove('invisible');
                errorCount += 1;
                this.errorCountTarget.innerText = errorCount;
                let p = document.createElement('p');
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
            block = false;

            if (completed === fields.length) {
                this.statusTarget.classList.remove('pending');
                this.statusTarget.classList.add('complete');
            }
        }
    }
}