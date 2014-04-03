$(function(){
    var _container = '.date-selector', 
    _dateselect = _container+' select';
    $(document).on('change', _dateselect, function(){
        _isFullDate = true;
        $(_dateselect).each(function(){
            if (!$(this).val() || $(this).val() == '0') _isFullDate = false;
        })
        if(!_isFullDate) return;
        
        // loader icon
        var $_icon  = $(this).parents('td').next().find('.validate-icon');
        //Disable all icons, ticks and krutilok
        $_icon = $();
        $_icon.removeClass('error success');
        $_icon.addClass('ajax-loader');       

        var $_day = $(_container+' #day'), $_month = $(_container+' #month'), $_year = $(_container+' #year');
        $.post($(_container).attr('rel'), {
            param   :  'birthday', 
            value   :  $_month.val()+'.'+$_day.val()+'.'+$_year.val(),
            format  : 'json'
        }, function (ajaxResponse){

            var _errorSelector = 'errors';
            $_icon.removeClass('ajax-loader');
            $(_container).parent().children('.' + _errorSelector).remove();
            
            if (ajaxResponse.result){
                $_icon.addClass('success');
                $('#to-step-two').removeAttr('disabled');
                $(_dateselect).removeClass('error');
               
            } else {
                $.each(ajaxResponse.msg, function(){
                    $(_container).after('<div class="' + _errorSelector + '">' + this + '</div>');
                });
                $('#to-step-two').attr('disabled', 'disabled');
                $(_dateselect).addClass('error');
            }
            if(typeof trySave == 'function'){
                trySave();
            }
        })
    })
})
