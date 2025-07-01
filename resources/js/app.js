// Import jQuery and set global variables
import $ from 'jquery';
window.$ = window.jQuery = $;

// Import Bootstrap core JavaScript
import 'bootstrap';

// Import Bootstrap 4 Toggle plugin for toggle switches
import 'bootstrap4-toggle';

// Import Bootstrap Tooltip and initialize all tooltips on page load
import { Tooltip } from 'bootstrap';
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new Tooltip(el);
    });
});

// Import MetisMenu plugin for sidebar menus
import 'metismenu';

// Import NProgress for showing loading progress bars
import NProgress from 'nprogress';
window.NProgress = NProgress;

// Import jQuery BlockUI plugin to block user interactions during requests
import 'jquery-blockui';

// Import PerfectScrollbar for better scrollbars
import PerfectScrollbar from 'perfect-scrollbar';
window.PerfectScrollbar = PerfectScrollbar;

// Import Toastr for showing notifications (alerts)
import toastr from 'toastr';
window.toastr = toastr;

// Import DataTables with Bootstrap 5 styling for tables
require('datatables.net-bs5');
require('datatables.net-responsive-bs5');

// Import Inputmask plugin for input formatting
import Inputmask from 'inputmask';

// Create jQuery plugin for Inputmask to apply masks easily
$.fn.inputmask = function (...args) {
    return this.each(function () {
        Inputmask(...args).mask(this);
    });
};

// Import Select2 plugin for enhanced select dropdowns
import 'select2';

// Import textarea-autosize to auto grow textarea height on input
import 'textarea-autosize';

// Import GridStack for drag-and-drop grid layouts
import { GridStack } from 'gridstack';
window.GridStack = GridStack;

// Import intlTelInput for international telephone input formatting
import intlTelInput from 'intl-tel-input';
window.intlTelInput = intlTelInput;

// Import jQuery Validation plugin for form validation
import 'jquery-validation';

// Import Date Range Picker plugin
import 'daterangepicker';

// Import Moment.js for date and time manipulation
import moment from 'moment';
window.moment = moment;

// Import jQuery Circle Progress for circular progress bars
import 'jquery-circle-progress';

// Import FilePond file upload plugin and Image Preview plugin
import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

window.FilePond = FilePond;
window.FilePondPluginImagePreview = FilePondPluginImagePreview;

// Register FilePond Image Preview plugin
FilePond.registerPlugin(FilePondPluginImagePreview);

// Import CodeMirror code editor core and modes
import CodeMirror from 'codemirror/lib/codemirror';
window.CodeMirror = CodeMirror;

// Import CodeMirror language modes for HTML, PHP, XML, CSS, and C-like languages
import 'codemirror/mode/htmlmixed/htmlmixed';
import 'codemirror/mode/php/php';
import 'codemirror/mode/xml/xml';
import 'codemirror/mode/css/css';
import 'codemirror/mode/clike/clike';

// Set global CodeMirror variable again (safe to keep)
window.CodeMirror = CodeMirror;
