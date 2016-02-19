(function( $ ) {

    $.fn.formhandler = function(options) {

        var defaults = {
            pageID: 0,
            contentID: 0,
            randomID: 0,
            lang: 0,
            ajaxSubmit: false,
            submitButtonSelector: "INPUT[type='submit']",
            validateFieldNames: '',
            submitStart: function(el) { },
            submitComplete: function(el, data) { },
            submitFinished: function(el, data) { },
            validateStart: function(el) { },
            validateComplete: function(el, data) { },
            validateFinished: function(el, data) { }
        };

        var settings = $.extend( {}, defaults, options );
        var formhandlerDiv = $(this);
        if(settings.ajaxSubmit) {
            formhandlerDiv.on('submit', 'form', function (e) {
                settings.submitStart.call(formhandlerDiv);
                var form = $(this);
                var url = '/index.php?eID=formhandler-ajaxsubmit&id=' + settings.pageID + '&uid=' + settings.contentID + '&L=' + settings.lang;
                var postData = form.serialize() + "&" + formhandlerDiv.find(settings.submitButtonSelector).attr("name") + "=submit";
                formhandlerDiv.find(".loading_ajax-submit").show();
                jQuery.ajax({
                    type: "post",
                    url: url,
                    data: postData,
                    dataType: "json",
                    success: function(data, textStatus) {
                        settings.submitComplete.call(formhandlerDiv, data);
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            var parsedFormData = $(data.form);
                            formhandlerDiv.html(parsedFormData.html());
                        }
                        settings.submitFinished.call(formhandlerDiv, data);
                    }
                });
                e.preventDefault();
            });
        }

        formhandlerDiv.on('click', 'a.formhandler_removelink', function (e) {
            var el = $(this);
            var url = el.attr("href");
            var container = el.closest("div[id^='Tx_Formhandler_UploadedFiles_']");
            container.load(url + '#' + container.attr("id"));
            e.preventDefault();
        });

        for (index = 0; index < settings.validateFields.length; ++index) {
            var shortFieldName = settings.validateFields[index];
            var fieldName = shortFieldName;
            if(settings.formValuesPrefix) {
                fieldName = settings.formValuesPrefix + '[' + fieldName + ']';
            }

            formhandlerDiv.on('blur', "input[name^='"+ fieldName + "']", function() {
                var field = $(this);
                var name = field.attr("name");
                var shortName = name.replace(settings.formValuesPrefix, '').replace("[", "").replace("]", "");
                var fieldVal = encodeURIComponent(field.val());
                if(field.attr("type") == "radio" || field.attr("type") == "checkbox") {
                    if (field.attr("checked") == "") {
                        fieldVal = "";
                    }
                }
                var loading = formhandlerDiv.find('#loading_' + shortName);
                var result = formhandlerDiv.find('#result_' + shortName);
                loading.show();
                result.hide();
                var url = '/index.php?eID=formhandler&id=' + settings.pageID + '&uid=' + settings.contentID + '&L=' + settings.lang;
                url += '&field=' + shortName + '&value=' + fieldVal;

                settings.validateStart.call(formhandlerDiv, shortName);
console.log(result);
                result.load(url, function() {
                    settings.validateComplete.call(formhandlerDiv, shortName, result);
                    loading.hide();
                    result.show();
                    isFieldValid = false;
                    if (result.find("SPAN.error").length > 0) {
                        result.data("isValid", false);
                    } else {
                        isFieldValid = true;
                        result.data("isValid", true);
                    }
                    settings.validateFinished.call(formhandlerDiv, shortName, result);
                });
            });
        }
    };

}( jQuery ));



