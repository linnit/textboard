
class Setting {
    constructor(formname, name, value) {
        this.formname = formname;
        this.name = name;
        this.value = value;

        this.form = document.querySelector(`form[name=${this.formname}]`);
        this.input = document.querySelector(`[id=${this.name}]`);

        this.initListeners();
    }

    initListeners() {
        this.input.addEventListener('change', () => {
            this.checkInput();
        });
    }

    checkInput() {
        if (this.input.type == "checkbox") {
            if (this.input.checked.toString() != this.value) {
                console.log("Checkbox change to ", this.input.checked.toString());
                this.value = this.input.checked.toString();
                this.submitForm();
            }
        } else if (this.input.value != this.value) {
            console.log("Input changed to ", this.input.value);
            this.value = this.input.value;
            this.submitForm();
        }
    }

    submitForm() {
        let data = new FormData(this.form);
        let httpRequest = new XMLHttpRequest();

        data.set('AJAX', true);

        httpRequest.onreadystatechange = function() {
            if (httpRequest.readyState === XMLHttpRequest.DONE && httpRequest.status === 200) {
                document.querySelector('.alert-container').innerHTML = httpRequest.responseText;
            }
        };

        httpRequest.open('POST', window.location.href);

        httpRequest.send(data);
    }
}

const settingDatasets = document.querySelectorAll('.setting-data');
let settings = [];

settingDatasets.forEach((s) => {
    settings.push(new Setting(s.dataset.formname, s.dataset.name, s.dataset.value));
});

const formButtons = document.querySelectorAll('form .btn-primary');

formButtons.forEach((b) => {
    b.remove();
});

