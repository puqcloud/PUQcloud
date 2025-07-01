function PUQajax(url, data, timeOut, button, method = 'POST', form = null) {

    if (form && form.length) {
        form.find('.invalid-feedback').remove();
        form.find('.is-invalid').removeClass('is-invalid');
    }

    return new Promise((resolve, reject) => {
        var originalText = button ? button.html() : '';
        var loadingText = '';

        if (button && button.hasClass('btn-icon-only')) {
            loadingText = '<i class="fa fa-fw fa-spin"></i>';
        } else {
            loadingText = '<i class="fa fa-fw fa-spin"></i> ' + translate("Loading...");
        }

        if (button) {
            button.prop('disabled', true);
            button.html(loadingText);
        }

        function handleError(response) {
            if (response.status === 'error' && response.errors) {
                const addMessages = (messages) => {
                    if (typeof messages === 'string') {
                        response.errors.push(messages);
                    } else if (Array.isArray(messages)) {
                        response.errors.push(...messages);
                    } else if (typeof messages === 'object') {
                        Object.values(messages).forEach(value => {
                            if (Array.isArray(value)) {
                                response.errors.push(...value);
                            } else {
                                response.errors.push(value);
                            }
                        });
                    }
                };

                if (response.message) {
                    addMessages(response.message);
                }

                const timeoutValue = (timeOut === 50) ? 20000 : timeOut * 10;
                alert_error(translate('Error'), response.errors, timeoutValue);
                return true;
            }
            return false;
        }

        function makeRequest() {
            var fullUrl = method === 'GET' && $.param(data) ? url + '?' + $.param(data) : url;

            $.ajax({
                url: fullUrl,
                type: method,
                contentType: method === 'GET' ? undefined : 'application/json',
                data: method === 'GET' ? undefined : JSON.stringify(data),
                success: function (response) {

                    if (handleError(response)) {
                        reject(response.errors);
                    }

                    if (response.status === 'success') {
                        if (response.message) {
                            alert_success(translate('Success'), [response.message], timeOut);
                        }
                        resolve(response);
                    }

                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, timeOut);
                        resolve();
                    }

                    resolve(response);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let response;

                    try {
                        response = jqXHR.responseJSON || JSON.parse(jqXHR.responseText);
                    } catch (e) {
                        response = {};
                    }

                    if (isJsonObject(response.message) && form && form.jquery && form.length > 0) {
                        showFormErrors(form, response);
                    }

                    if (!handleError(response)) {
                        const timeoutValue = (timeOut === 50) ? 10000 : timeOut * 10;
                        alert_error(
                            translate('Error'),
                            [translate('No response from the server. Try again later.')],
                            timeoutValue
                        );
                    }
                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, timeOut);
                        resolve();
                    }
                    reject();
                },
                complete: function () {
                    if (button) {
                        button.prop('disabled', false);
                        button.html(originalText);
                    }
                }
            });
        }

        makeRequest();
    });
}

function alert_error(title, descriptions, timeOut) {
    var descriptionText = descriptions.join('<br>');
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: timeOut,
        extendedTimeOut: timeOut / 2,
        positionClass: 'toast-bottom-full-width',
        showDuration: 300,
        hideDuration: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };
    toastr.error(descriptionText, title);
}

function alert_success(title, descriptions, timeOut) {
    var descriptionText = descriptions.join('<br>');
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: timeOut,
        extendedTimeOut: timeOut / 2,
        positionClass: 'toast-bottom-full-width',
        showDuration: 300,
        hideDuration: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };
    toastr.success(descriptionText, title);
}

function initializeDataTable(tableId, ajaxUrl, columnsConfig, getCustomData, customOptions = {}) {

    if ($.fn.DataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
        $(tableId).empty();
    }

    var defaultOptions = {
        pagingType: 'numbers',
        responsive: true,
        processing: true,
        scrollCollapse: true,
        paging: true,
        searching: true,
        info: true,
        serverSide: true,
        pageLength: 10,
        lengthMenu: [10, 20, 50, 100, 150, 200, 500, 1000],
        language: {
            processing: translate('processing'),
            search: translate('search'),
            lengthMenu: translate('lengthMenu'),
            info: translate('info'),
            infoEmpty: translate('infoEmpty'),
            infoFiltered: translate('infoFiltered'),
            infoPostFix: translate('infoPostFix'),
            loadingRecords: translate('loadingRecords'),
            zeroRecords: translate('zeroRecords'),
            emptyTable: translate('emptyTable'),
            paginate: {
                first: translate('first'),
                previous: translate('previous'),
                next: translate('next'),
                last: translate('last')
            },
            aria: {
                sortAscending: translate('sortAscending'),
                sortDescending: translate('sortDescending')
            },
            thousands: translate('thousands'),
            decimal: translate('decimal')
        },
        ajax: function (data, callback, settings) {

            if (typeof getCustomData !== 'undefined') {
                var customData = getCustomData();
            } else {
                var customData = {};
            }

            var requestData = $.extend({}, data, customData);

            PUQajax(ajaxUrl, requestData, 3000, null, 'GET')
                .then(function (response) {
                    if (response.data) {
                        callback(response.data.original);
                    } else {
                        callback({
                            draw: data.draw,
                            recordsTotal: 0,
                            recordsFiltered: 0,
                            data: []
                        });
                    }
                })
                .catch(function () {
                    alert_error(translate('Error'), [translate('Failed to load data')], 30000);
                });
        },
        columns: columnsConfig
    };
    var finalOptions = $.extend({}, defaultOptions, customOptions);
    for (var key in customOptions) {
        if (Array.isArray(customOptions[key])) {
            finalOptions[key] = customOptions[key];
        }
    }

    var dataTable = $(tableId).DataTable(finalOptions);

    return dataTable;

}

function initializeAutoReloadTable(options) {
    var reloadInterval = parseInt($(options.intervalSelectId).val());
    var countdown = reloadInterval;

    $(options.circleProgressId).circleProgress({
        value: 0,
        size: 52,
        lineCap: "round",
        fill: {color: options.progressColor}
    });

    function reloadTableProgress() {
        if ($(options.autoReloadCheckboxId).is(':checked')) {
            var progressValue = countdown / reloadInterval;

            $(options.circleProgressId).circleProgress({
                value: progressValue
            }).on("circle-animation-progress", function (event, progress, stepValue) {
                $(this).find("small").html("<span>" + Math.round(progressValue * 100) + "%</span>");
            });

            if (countdown === 0) {
                if ($(options.autoReloadCheckboxId).is(':checked')) {
                    options.dataTable.ajax.reload(null, false);
                }
                countdown = reloadInterval;
            } else {
                countdown--;
            }
        } else {
            $(options.circleProgressId).circleProgress({
                value: 0
            }).on("circle-animation-progress", function (event, progress, stepValue) {
                $(this).find("small").html("<span>0%</span>");
            });
            countdown = reloadInterval;
        }
    }

    $(options.intervalSelectId).on('change', function () {
        reloadInterval = parseInt($(this).val());
        countdown = reloadInterval;
    });

    setInterval(reloadTableProgress, 1000);
}

function formatDateISO(isoString) {
    return isoString;
    if (!isoString) {
        return '';
    }
    const date = new Date(isoString);
    if (isNaN(date.getTime())) {
        throw new Error(translate('Invalid date string'));
    }
    const pad = (num) => num.toString().padStart(2, '0');
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());
    const hours = pad(date.getHours());
    const minutes = pad(date.getMinutes());
    const seconds = pad(date.getSeconds());
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function formatDateWithoutTimezone(isoString) {
    return isoString;
    if (!isoString) {
        return '';
    }
    const date = new Date(isoString);
    const day = String(date.getUTCDate()).padStart(2, '0');
    const month = String(date.getUTCMonth() + 1).padStart(2, '0');
    const year = date.getUTCFullYear();
    const hours = String(date.getUTCHours()).padStart(2, '0');
    const minutes = String(date.getUTCMinutes()).padStart(2, '0');
    const seconds = String(date.getUTCSeconds()).padStart(2, '0');
    return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
}

function renderStatus(data) {
    return data ?
        '<i class="text-danger fa fa-times-circle"></i>' :
        '<i class="text-success fa fa-check-circle"></i>';
}

function renderServiceStatus(status) {
    const colorMap = {
        'active': 'success',
        'completed': 'success',
        'suspended': 'warning',
        'pause': 'warning',
        'pending': 'info',
        'deploying': 'info',
        'terminated': 'dark',
        'fraud': 'dark',
        'not_found': 'dark',
        'cancelled': 'alternate',
        'processing': 'info',
        'failed': 'danger'
    };
    const key = (status || '').toLowerCase();
    const color = colorMap[key] || 'secondary';

    return `<div class="badge bg-${color} text-uppercase">${translate(status)}</div>`;
}

function renderInvoiceType(type) {
    const typeMap = {
        'invoice': {
            icon: 'fa-file-invoice-dollar',
            color: 'primary',
            label: translate('Invoice')
        },
        'proforma': {
            icon: 'fa-file-alt',
            color: 'info',
            label: translate('Proforma')
        },
        'credit_note': {
            icon: 'fa-file-invoice',
            color: 'warning',
            label: translate('Credit Note')
        }
    };

    const key = (type || '').toLowerCase();
    const data = typeMap[key] || {
        icon: 'fa-file',
        color: 'secondary',
        label: type || 'Unknown'
    };

    return `<div class="badge bg-${data.color}">
                <i class="fas ${data.icon} me-1"></i> ${data.label}
            </div>`;
}

function renderInvoiceStatus(status) {
    const statusMap = {
        'draft': {
            icon: 'fa-pencil-alt',
            color: 'secondary',
            label: translate('Draft')
        },
        'unpaid': {
            icon: 'fa-clock',
            color: 'danger',
            label: translate('Unpaid')
        },
        'paid': {
            icon: 'fa-check-circle',
            color: 'success',
            label: translate('Paid')
        },
        'canceled': {
            icon: 'fa-ban',
            color: 'dark',
            label: translate('Canceled')
        },
        'refunded': {
            icon: 'fa-undo-alt',
            color: 'info',
            label: translate('Refunded')
        },
        'invoiced': {
            icon: 'fa-file-invoice-dollar',
            color: 'primary',
            label: translate('Invoiced')
        },
        'deleted': {
            icon: 'fa-trash',
            color: 'danger',
            label: translate('Deleted')
        }
    };

    const key = (status || '').toLowerCase();
    const data = statusMap[key] || {
        icon: 'fa-question-circle',
        color: 'secondary',
        label: status || 'Unknown'
    };

    return `<div class="badge bg-${data.color}">
                <i class="fas ${data.icon} me-1"></i> ${data.label}
            </div>`;
}

function renderCurrencyAmount(amount, currencyCode = '') {
    return `
        <div class="widget-chart-content">
            <div class="widget-chart-flex">
                <div class="widget-numbers">
                    <div class="widget-chart-flex text-nowrap">
                        <div class="fsize-1 text-nowrap">
                            <span>${amount}</span>
                            <small class="opacity-5">${currencyCode}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderCurrencyAmountSmall(amount, currencyCode = '') {
    return `
        <div class="widget-chart-content fs-7">
            <div class="widget-chart-flex">
                <div class="widget-numbers">
                    <div class="widget-chart-flex text-nowrap">
                        <div class="text-nowrap">
                            <span>${amount}</span>
                            <small class="opacity-5">${currencyCode}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}


function renderTransactionType(type) {
    const colorMap = {
        'charge': 'danger',
        'payment': 'success',
        'refund': 'warning',
        'adjustment': 'info',
    };

    const key = (type || '').toLowerCase();
    const color = colorMap[key] || 'secondary';

    return `<div class="badge bg-${color} text-uppercase">${translate(type)}</div>`;
}

function renderEditButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="edit-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-warning">
                        <i class="fa fa-edit"></i>
                    </button>`;
}

function idleStatus(data) {
    return data
        ? '<i class="fa fa-pause-circle text-warning" title="Paused"></i>'
        : '<i class="fa fa-play-circle text-success" title="Active"></i>';
}

function renderDeleteButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="delete-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger">
                        <i class="fa fa-trash-alt"></i>
                    </button>`;
}

function renderActivateButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="activate-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-plug"></i>
                    </button>`;
}

function renderDeactivateButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="deactivate-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger">
                        <i class="fa fa-times"></i>
                    </button>`;
}

function renderDisconnectButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="disconnect-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-primary">
                <i class="fa fa-unlink"></i>
            </button>`;
}

function renderUpdateButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="update-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-info">
                        <i class="fa fa-sync"></i>
                    </button>`;
}

function renderSettingsButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="settings-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-primary">
                        <i class="fa fa-cog"></i>
                    </button>`;
}

function renderViewButton(modelUrl) {
    return `<button data-model-url="${modelUrl}" class="view-btn mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-primary">
                    <i class="fa fa-eye"></i>
                </button>`;
}

function renderOrderButtons(row) {
    return `
            <div role="group" class="mb-2 btn-group btn-group-toggle">
                <button class="btn btn-sm btn-outline-2x btn btn-outline-primary move-down btn-icon-only" data-uuid="${row.uuid}" data-order="${row.order}">
                    <i class="fa fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-2x btn btn-outline-primary">${row.order}</button>
                <button class="btn btn-sm btn-outline-2x btn btn-outline-primary move-up btn-icon-only" data-uuid="${row.uuid}" data-order="${row.order}">
                    <i class="fa fa-arrow-up"></i>
                </button>
           </div>
    `;
}

function blockUI(elementId, element = null) {
    $.blockUI.defaults = {
        css: {
            border: 'none',
            padding: '20px',
            backgroundColor: 'rgba(255, 255, 255, 0.9)',
            color: '#333',
            borderRadius: '15px',
            boxShadow: '0 0 15px rgba(0, 0, 0, 0.2)',
            width: 'auto',
            height: 'auto',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            textAlign: 'center',
        },
        overlayCSS: {
            backgroundColor: '#333',
            opacity: 0.3,
            cursor: 'wait',
            borderRadius: '5px',
            boxShadow: '0 0 30px rgba(0, 0, 0, 0.7)',
        },
        message: $(
            '<div class="loader-container">' +
            '<div class="line-scale-pulse-out">' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '<div class="bg-primary"></div>' +
            '</div>' +
            '<p style="margin-top: 10px; font-size: 14px; color: #333;">' + translate('Loading...') + '</p>' +
            '</div>'
        ),
    };

    if (element === null) {
        $("#" + elementId).block($.blockUI.defaults);
    } else {
        element.block($.blockUI.defaults);
    }
}

function unblockUI(elementId, element = null) {
    if (element === null) {
        $("#" + elementId).unblock();
    } else {
        element.unblock();
    }
};

function initializeSelect2(element, url, selectedValues = [], method = 'GET', timeout = 5000, options = {}, gets = {}, callback = null) {
    if (!element || !$(element).is('select')) {
        console.error('Invalid element or not a <select> element');
        return;
    }

    $(element).css('width', '100%');
    $(element).val(null).trigger('change');

    if (!$(element).data('select2')) {
        let defaultOptions = {
            theme: "bootstrap4",
            width: '100%',
            ajax: {
                transport: function (params, success, failure) {
                    params.data = $.extend(true, {}, params.data, gets);
                    PUQajax(url, params.data, timeout, null, method)
                        .then(function (response) {
                            success(response.data);
                            if (typeof callback === 'function') {
                                setTimeout(() => callback(), 50);
                            }
                        })
                        .catch(function (error) {
                            failure(error);
                        });
                },
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                }
            },
            language: {
                errorLoading: () => translate('error_loading'),
                inputTooLong: (args) => translate('input_too_long').replace('{overChars}', args.input.length - args.maximum).replace('{s}', (args.input.length - args.maximum > 1 ? "s" : "")),
                inputTooShort: (args) => translate('input_too_short').replace('{remainingChars}', args.minimum - args.input.length),
                loadingMore: () => translate('loading_more'),
                maximumSelected: (args) => translate('maximum_selected').replace('{maximum}', args.maximum).replace('{s}', (args.maximum > 1 ? "s" : "")),
                noResults: () => translate('no_results'),
                searching: () => translate('searching')
            },
            placeholder: translate('select_option'),
        };

        let mergedOptions = $.extend(true, {}, defaultOptions, options);

        $(element).select2(mergedOptions);
    }

    if ($(element).prop('multiple')) {
        $(element).empty();
    }

    if (selectedValues) {
        if ($(element).prop('multiple')) {
            selectedValues.forEach(function (selectedValue) {
                if ($(element).find(`option[value="${selectedValue.id}"]`).length === 0) {
                    let option = new Option(selectedValue.text, selectedValue.id, true, true);
                    $(element).append(option);
                }
            });
        } else {
            if (selectedValues) {
                let option = new Option(selectedValues.text, selectedValues.id, true, true);
                $(element).append(option);
            }
        }
        $(element).trigger('change');
    }

    if (typeof callback === 'function') {
        setTimeout(() => callback(), 100);
    }
}

function buildMultipleSelect(element, url, selectedValues, method = 'GET', timeout = 5000) {
    if (selectedValues == null) {
        selectedValues = [];
    }
    if (!element || element.tagName.toLowerCase() !== 'select') {
        console.error('Invalid element or not a <select> element');
        return;
    }

    element.innerHTML = '';

    if ($(element).data('select2')) {
        $(element).select2('destroy');
    }
    PUQajax(url, {}, timeout, null, method)
        .then(function (response) {
            response.data.forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.uuid;
                option.textContent = item.name;
                element.appendChild(option);
            });

            var options = element.options;
            for (var i = 0; i < options.length; i++) {
                if (selectedValues.includes(options[i].value)) {
                    options[i].selected = true;
                }
            }

            if (selectedValues.length > 0) {
                element.dispatchEvent(new Event('change'));
            }

            $(element).select2({});
        })
        .catch(function (error) {
            console.error('Error fetching data:', error);
        });
}

function serializeForm(form, forcePromise = false) {
    const obj = {};
    const filePromises = [];
    let hasFiles = false;

    form.find(':input').each(function () {
        const item = $(this);
        const name = item.attr('name');
        if (!name) return;

        if (item.is(':file')) {
            const files = item[0].files;
            if (files && files.length > 0) {
                hasFiles = true;
                obj[name] = [];
                Array.from(files).forEach(file => {
                    const promise = new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            obj[name].push({
                                name: file.name,
                                type: file.type,
                                size: file.size,
                                content: e.target.result.split(',')[1] // только base64
                            });
                            resolve();
                        };
                        reader.readAsDataURL(file);
                    });
                    filePromises.push(promise);
                });
            }
            return;
        }

        let value;
        if (item.is(':checkbox')) {
            value = item.is(':checked') ? "yes" : "no";
        } else if (item.is('select[multiple]')) {
            value = item.val() || [];
        } else {
            value = item.val();
        }

        const objectMatch = name.match(/^(\w+)\[([^\]]+)\]$/);

        if (objectMatch) {
            const key = objectMatch[1];
            const subKey = objectMatch[2];

            if (!obj[key]) obj[key] = {};
            obj[key][subKey] = value;

        } else {
            obj[name] = value;
        }
    });

    if (hasFiles || forcePromise) {
        return Promise.all(filePromises).then(() => obj);
    } else {
        return obj;
    }
}


function translate(key) {
    if (window.translations && typeof window.translations === 'object') {
        return window.translations[key] !== undefined ? window.translations[key] : key;
    }
    return key;
}

function getActionColor(action) {
    switch (action) {
        case 'api':
            return 'bg-primary';
        case 'web':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

function getMethodColor(method) {
    switch (method) {
        case 'GET':
            return 'bg-info';
        case 'POST':
            return 'bg-focus';
        case 'PUT':
            return 'bg-warning';
        case 'DELETE':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function getLogLevelColor(level) {
    switch (level.toLowerCase()) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'error':
            return 'bg-danger';
        case 'warning':
            return 'bg-warning';
        case 'notice':
            return 'bg-info';
        case 'info':
            return 'bg-primary';
        case 'debug':
            return 'bg-alternate';
        default:
            return 'bg-dark';
    }
}

function showFormErrors(form, message) {
    if (!form || !message || typeof message.message !== 'object') {
        console.error('Invalid form or message data');
        return;
    }

    form.find('.invalid-feedback').remove();
    form.find('.is-invalid').removeClass('is-invalid').addClass('is-valid');

    if (!message.message) {
        console.warn('No error messages found in message object');
        return;
    }

    $.each(message.message, function (field, messages) {
        var input = form.find(`[name="${field}"]`);

        if (!input.length) {
            console.warn(`Input field "${field}" not found in form`);
            return;
        }

        input.addClass('is-invalid');

        if (!Array.isArray(messages)) {
            console.error(`Messages for field "${field}" are not in the expected format`);
            return;
        }

        $.each(messages, function (index, message) {
            if (typeof message === 'string') {
                input.after(`<em class="invalid-feedback">${message}</em>`);
            }
        });
    });
}

function resetFormValidation(form) {
    if (!form) {
        console.error('Invalid form object');
        return;
    }
    form.find('.invalid-feedback').remove();
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.is-valid').removeClass('is-valid');

}

function isJsonObject(obj) {
    return obj && typeof obj === 'object' && !Array.isArray(obj);
}

function getQueueStatusLabelClass(status) {
    switch (status) {
        case 'completed':
            return 'success';
        case 'queued':
            return 'secondary';
        case 'failed':
            return 'danger';
        case 'pending':
            return 'warning';
        case 'processing':
            return 'primary';
        default:
            return 'info';
    }
}

function getClientStatusLabelClass(status) {
    switch (status) {
        case 'new':
            return 'primary';
        case 'active':
            return 'success';
        case 'inactive':
            return 'secondary';
        case 'closed':
            return 'danger';
        case 'fraud':
            return 'dark';
        default:
            return 'info';
    }
}

function getModuleStatusLabelClass(status) {
    switch (status) {
        case 'active':
            return 'success';
        case 'inactive':
            return 'secondary';
        case 'error':
            return 'danger';
        default:
            return 'info';
    }
}

function initPerfectScrollbar() {
    setTimeout(function () {
        if ($(".scrollbar-container")[0]) {
            $(".scrollbar-container").each(function () {
                const ps = new PerfectScrollbar($(this)[0], {
                    wheelSpeed: 2,
                    wheelPropagation: false,
                    minScrollbarLength: 20,
                });
            });

            const ps = new PerfectScrollbar(".scrollbar-sidebar", {
                wheelSpeed: 2,
                wheelPropagation: true,
                minScrollbarLength: 20,
            });
        }
    }, 1000);
}

function loadSimpleWidget(id, heading, subheading) {
    const widgetHtml = `
            <div class="card mb-3 widget-content" data-widget-key="${id}">
                <div class="widget-content-wrapper">
                    <div class="widget-content-left">
                        <div class="widget-heading">${translate(heading)}</div>
                        <div class="widget-subheading">${translate(subheading)}</div>
                    </div>
                </div>
            </div>
        `;

    $('#' + id).html(widgetHtml);
}

function generateUrl(template, params) {
    let url = template;
    for (const key in params) {
        url = url.replace(`__${key}__`, encodeURIComponent(params[key]));
    }
    return url;
}

function initializeDatePicker(element, date) {
    if (!date) {
        element.prop("disabled", true);
        return;
    }

    const locale = {
        format: "YYYY-MM-DD HH:mm",
        applyLabel: translate("Apply"),
        cancelLabel: translate("Cancel"),
        daysOfWeek: [
            translate("Su"),
            translate("Mo"),
            translate("Tu"),
            translate("We"),
            translate("Th"),
            translate("Fr"),
            translate("Sa")
        ],
        monthNames: [
            translate("January"),
            translate("February"),
            translate("March"),
            translate("April"),
            translate("May"),
            translate("June"),
            translate("July"),
            translate("August"),
            translate("September"),
            translate("October"),
            translate("November"),
            translate("December")
        ],
        firstDay: 1
    };

    element.val(date);
    element.daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        showDropdowns: true,
        autoUpdateInput: false,
        startDate: date,
        locale: locale
    }).on("apply.daterangepicker", function (ev, picker) {
        $(this).val(picker.startDate.format(locale.format));
    });

}

function linkify(text, noLabel = false) {
    const regex = /\b(\w+):([0-9a-fA-F\-]{36})/gi;

    return text.replace(regex, (match, label, uuid) => {
        const url = window.routes.adminRedirect.replace('__label__', label.toLowerCase()).replace('__uuid__', uuid);

        if (noLabel) {
            return `<a href="${url}" target="_blank">${uuid}</a>`;
        } else {
            return `<a href="${url}" target="_blank">${label}:${uuid}</a>`;
        }
    });
}

function startCountdown(seconds, $element) {
    function formatTime(sec) {
        const h = String(Math.floor(sec / 3600)).padStart(2, '0');
        const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    $element.text(formatTime(seconds));

    const interval = setInterval(() => {
        seconds--;
        $element.text(formatTime(seconds));

        if (seconds <= 0) {
            clearInterval(interval);
            $('#universalModal').modal('hide');
            toastr.warning(translate("Time expired"));
        }
    }, 1000);
}

function startCountdown(el, seconds, label = '') {
    if (!el || typeof seconds !== 'number' || seconds < 0) return;

    function updateCountdown() {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        const timeStr =
            String(hrs).padStart(2, '0') + ':' +
            String(mins).padStart(2, '0') + ':' +
            String(secs).padStart(2, '0');

        el.textContent = (label ? label + ': ' : '') + timeStr;

        if (seconds <= 0) {
            clearInterval(timer);
        }

        seconds--;
    }

    updateCountdown();
    const timer = setInterval(updateCountdown, 1000);
}

function renderImageFields(images, cssClass) {
    let filepondInstances = [];
    const fields = images._fields ?? {};
    const container = $('#filepond-container');
    container.empty();

    Object.keys(fields)
        .sort((a, b) => fields[a].order - fields[b].order)
        .forEach(fieldKey => {
            const field = fields[fieldKey];
            const imageUrl = images[fieldKey] ?? '';
            const id = 'uploader-' + fieldKey;

            const html = `
                <div class="${cssClass}">
                    <div class="card text-center">
                        <div class="card-header">
                            <label class="form-label mb-0">${translate(field.label)}</label>
                        </div>
                        <div class="card-body">
                            <input type="file"
                                   class="filepond"
                                   id="${id}"
                                   name="${fieldKey}"
                                   data-field="${fieldKey}"
                                   data-model-type="${field.model_type}"
                                   data-model-uuid="${field.model_uuid}"
                                   data-uuid="${field.uuid ?? ''}"
                                   data-url="${imageUrl}">
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
        });

    // FilePond global UI translations
    FilePond.setOptions({
        labelIdle: translate('Drag & Drop your files or <span class="filepond--label-action">Browse</span>'),
        labelFileProcessing: translate('Uploading'),
        labelFileProcessingComplete: translate('Upload complete'),
        labelFileProcessingError: translate('Error during upload'),
        labelTapToUndo: translate('Tap to undo'),
        labelTapToCancel: translate('Tap to cancel'),
        labelTapToRetry: translate('Tap to retry'),
        labelButtonRemoveItem: translate('Remove'),
        labelButtonAbortItemLoad: translate('Abort'),
        labelButtonRetryItemLoad: translate('Retry'),
        labelButtonAbortItemProcessing: translate('Abort'),
        labelButtonUndoItemProcessing: translate('Undo'),
        labelButtonRetryItemProcessing: translate('Retry'),
        labelButtonProcessItem: translate('Upload'),
    });

    $('.filepond').each(function () {
        const input = this;
        const field = $(input).data('field');
        const imageUrl = $(input).data('url');
        const uuid = $(input).data('uuid');

        const pond = FilePond.create(input, {
            allowMultiple: false,
            files: imageUrl ? [{
                source: imageUrl,
                options: {
                    type: 'local',
                    metadata: { uuid }
                }
            }] : [],
            server: {
                load: (source, load, error, progress, abort) => {
                    const request = new XMLHttpRequest();
                    request.open('GET', source, true);
                    request.responseType = 'blob';

                    request.onload = function () {
                        if (request.status >= 200 && request.status < 300) {
                            load(request.response);
                        } else {
                            error(translate('Failed to load image'));
                        }
                    };

                    request.onerror = () => error(translate('Load error'));
                    request.onabort = abort;
                    request.send();
                },
                process: {
                    url: window.routes.adminApiFileImageUpload,
                    method: 'POST',
                    ondata: (formData) => {
                        formData.append('model_type', $(input).data('model-type'));
                        formData.append('model_uuid', $(input).data('model-uuid'));
                        formData.append('field', field);
                        return formData;
                    },
                    onload: response => {
                        const res = JSON.parse(response);
                        if (res.status === 'success' && res.data) {
                            $(input).data('uuid', res.data.uuid);
                            return res.data.uuid;
                        } else {
                            throw new Error(translate(res.message || 'Upload failed'));
                        }
                    },
                    onerror: response => {
                        const res = JSON.parse(response);
                        return translate(res.message || 'Upload failed');
                    }
                },
                revert: (uniqueFileId, load, error) => {
                    const xhr = new XMLHttpRequest();
                    const formData = new FormData();
                    const fileId = uniqueFileId || $(input).data('uuid');
                    formData.append('uuid', fileId);

                    xhr.open('POST', window.routes.adminApiFileImageDelete, true);
                    xhr.onload = function () {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.status === 'success') {
                                    $(input).data('uuid', '');
                                    load();
                                } else {
                                    error(translate('Delete failed: ') + (response.errors ? response.errors.join(', ') : response.message));
                                }
                            } catch (e) {
                                error(translate('Invalid server response'));
                            }
                        } else {
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                error(translate('Delete failed: ') + (errorResponse.errors ? errorResponse.errors.join(', ') : translate('Server error')));
                            } catch (e) {
                                error(translate('Delete failed: ') + xhr.status);
                            }
                        }
                    };
                    xhr.onerror = function () {
                        error(translate('Network error during delete'));
                    };
                    xhr.send(formData);
                },
                remove: (source, load, error) => {
                    const xhr = new XMLHttpRequest();
                    const formData = new FormData();
                    const fileUuid = $(input).data('uuid');

                    if (fileUuid) {
                        formData.append('uuid', fileUuid);
                        xhr.open('POST', window.routes.adminApiFileImageDelete, true);
                        xhr.onload = function () {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.status === 'success') {
                                        $(input).data('uuid', '');
                                        load();
                                    } else {
                                        error(translate('Remove failed: ') + (response.errors ? response.errors.join(', ') : response.message));
                                    }
                                } catch (e) {
                                    error(translate('Invalid server response'));
                                }
                            } else {
                                try {
                                    const errorResponse = JSON.parse(xhr.responseText);
                                    error(translate('Remove failed: ') + (errorResponse.errors ? errorResponse.errors.join(', ') : translate('Server error')));
                                } catch (e) {
                                    error(translate('Remove failed'));
                                }
                            }
                        };
                        xhr.onerror = () => error(translate('Remove error'));
                        xhr.send(formData);
                    } else {
                        load();
                    }
                }
            }
        });

        filepondInstances.push(pond);
    });
}


document.addEventListener('DOMContentLoaded', function () {
    NProgress.configure({
        showSpinner: false,
        trickleSpeed: 200,
        minimum: 0.08
    });

    let activeRequests = 0;
    let delayTimer;
    let progressTimeout;

    function setNProgressColor(color) {
        const existingStyle = document.getElementById('nprogress-custom-color');
        if (existingStyle) {
            existingStyle.remove();
        }

        const style = document.createElement('style');
        style.id = 'nprogress-custom-color';
        style.textContent = `
                #nprogress .bar {
                    background: ${color} !important;
                }
                #nprogress .peg {
                    box-shadow: 0 0 10px ${color}, 0 0 5px ${color} !important;
                }
            `;
        document.head.appendChild(style);
    }
    setNProgressColor('linear-gradient(90deg, #667eea 0%, #764ba2 100%)');

    function isDownloadUrl(url) {
        if (!url) return false;
        return /\.(pdf|zip|rar|7z|exe|msi|dmg|tar\.gz|tar\.bz2|gz|bz2|docx?|xlsx?|pptx?|mp3|mp4|avi|mov|wmv|flv|mkv|jpg|jpeg|png|gif|svg|webp|ico|apk|deb|rpm|bin|iso)(\?.*)?$/i.test(url);
    }

    function isDownloadRequest(url, headers = {}) {
        if (isDownloadUrl(url)) return true;

        const contentDisposition = headers['content-disposition'] || headers['Content-Disposition'];
        if (contentDisposition && contentDisposition.includes('attachment')) {
            return true;
        }

        return false;
    }

    function startProgress(url, headers = {}) {
        if (isDownloadRequest(url, headers)) return;

        clearTimeout(delayTimer);
        clearTimeout(progressTimeout);
        delayTimer = setTimeout(() => {
            NProgress.start();
            progressTimeout = setTimeout(() => {
                NProgress.done();
            }, 3000);
        }, 100);
    }

    function stopProgress() {
        clearTimeout(delayTimer);
        clearTimeout(progressTimeout);
        setTimeout(() => {
            if (activeRequests <= 0) {
                NProgress.done();
            }
        }, 150);
    }

    window.addEventListener('beforeunload', function () {
        NProgress.start();
    });

    window.addEventListener('load', function () {
        NProgress.done();
    });

    document.body.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (
            !href ||
            href.startsWith('#') ||
            href.startsWith('javascript') ||
            link.target === '_blank' ||
            link.download !== null ||
            isDownloadUrl(href)
        ) return;

        startProgress(href);
    });

    (function () {
        const originalAssign = window.location.assign;
        const originalReplace = window.location.replace;
        const originalHref = Object.getOwnPropertyDescriptor(window.location.__proto__, 'href');

        function patchRedirect(fn) {
            return function (url) {
                if (isDownloadUrl(url)) {
                    fn.call(window.location, url);
                    return;
                }
                startProgress(url);
                setTimeout(() => fn.call(window.location, url), 100);
            };
        }

        window.location.assign = patchRedirect(originalAssign);
        window.location.replace = patchRedirect(originalReplace);

        if (originalHref && originalHref.set) {
            Object.defineProperty(window.location, 'href', {
                set: function (url) {
                    if (isDownloadUrl(url)) {
                        originalHref.set.call(window.location, url);
                        return;
                    }
                    startProgress(url);
                    setTimeout(() => originalHref.set.call(window.location, url), 100);
                },
                get: originalHref.get,
                configurable: true,
                enumerable: true
            });
        }
    })();

    if (window.fetch) {
        const origFetch = window.fetch;
        window.fetch = function (input, init) {
            let url = typeof input === 'string' ? input : (input.url || '');

            if (isDownloadUrl(url)) {
                return origFetch(input, init);
            }

            activeRequests++;
            startProgress(url);

            return origFetch(input, init)
                .then(response => {
                    const contentDisposition = response.headers.get('content-disposition');
                    if (contentDisposition && contentDisposition.includes('attachment')) {
                        activeRequests--;
                        stopProgress();
                        return response;
                    }
                    return response;
                })
                .finally(() => {
                    activeRequests--;
                    stopProgress();
                });
        };
    }

    const origOpen = XMLHttpRequest.prototype.open;
    const origSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url, ...rest) {
        this._requestUrl = url;

        if (isDownloadUrl(url)) {
            return origOpen.apply(this, [method, url, ...rest]);
        }

        const loadendHandler = () => {
            const contentDisposition = this.getResponseHeader('content-disposition');
            if (contentDisposition && contentDisposition.includes('attachment')) {
                activeRequests--;
                stopProgress();
                return;
            }

            activeRequests--;
            stopProgress();
        };

        this.addEventListener('loadend', loadendHandler, { once: true });

        activeRequests++;
        startProgress(url);
        return origOpen.apply(this, [method, url, ...rest]);
    };

    if (window.axios) {
        window.axios.interceptors.request.use(config => {
            if (isDownloadUrl(config.url)) {
                return config;
            }
            activeRequests++;
            startProgress(config.url);
            return config;
        });

        window.axios.interceptors.response.use(response => {
            const contentDisposition = response.headers['content-disposition'];
            if (!contentDisposition || !contentDisposition.includes('attachment')) {
                activeRequests--;
                stopProgress();
            }
            return response;
        }, error => {
            activeRequests--;
            stopProgress();
            return Promise.reject(error);
        });
    }

    if (window.jQuery) {
        $(document).ajaxSend((event, jqXHR, ajaxOptions) => {
            if (isDownloadUrl(ajaxOptions.url)) return;
            activeRequests++;
            startProgress(ajaxOptions.url);
        });

        $(document).ajaxComplete((event, jqXHR, ajaxOptions) => {
            const contentDisposition = jqXHR.getResponseHeader('content-disposition');
            if (!contentDisposition || !contentDisposition.includes('attachment')) {
                activeRequests--;
                stopProgress();
            }
        });
    }

    window.addEventListener('focus', function() {
        setTimeout(() => {
            if (activeRequests <= 0) {
                NProgress.done();
            }
        }, 500);
    });
});
