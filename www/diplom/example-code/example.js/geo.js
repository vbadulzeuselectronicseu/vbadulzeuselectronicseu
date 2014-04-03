/**
 * popup with GEO select interface
 *
 */
var Geo = {

    theNewHeyss : '',

    useAutocomplete: true,


    // options

    _dialogId: '#geoDialog', // selector, used in _getDialog()

    _dialogClasses: 'dark-dialog without-btns confirm-dialog close-outside', // string, used in _getDialog()

    _ajaxUrlForChild: '/index/ajax-geo-tree-for-child', // url, used in _showForChildId()

    _ajaxUrlForParent: '/index/ajax-geo-list-for-parent', // url, used in _showForParentId()

    // service function, debug output
    _log: function (){
        // console.log('GEO: ', this._selectedParentId, this._selectedId,': ', arguments);
    },

    // inner data holder, cache
    _data: {},

    _selectedId: null,
    _selectedParentId: null,

    // interface
    show: function(options){
        var self = this;
        self._log('show(options)', options);

        // check data
        options = options || {};
        childId =( options.id ? Number(options.id) : 0 );
        parentId =( options.parent ? Number(options.parent) : self._getParentByChild(childId) );

        if(options.onSelect){
            self.onSelect = options.onSelect;
        }

        if(childId && false === parentId){
            // get tree by ajax, then continue
            self._ajaxByChild(childId);
            self._log('show() paused for ajax');
            return;
        }

        if(!self._data[parentId]){
            // get data by ajax, then continue
            self._ajaxByParent(parentId, childId);
            self._log('show() paused for ajax');
            return;
        }

        // start show
        var _dialog = self._getDialog();
        if(!_dialog){
            self._log('show() confused - no dialog');
            return;
        }

        self._selectedParentId = parentId;
        self._selectedId = childId;

        //if(parentId && '0'!=parentId){
        //self._backBtn.show();
        //}else{
        //self._backBtn.hide();
        //}

        // show title for _selectedId
        self._setTitle();

        // show list
        var _select = _dialog.find('select');
        if(!_select){
            self._log('show() confused - no select');
            return;
        }

        var _items = self._data[parentId];
        if(!_items){
            self._log('show() confused - no data');
            return;
        }

        _select.empty().scrollTop();
        if(parentId == 0){
            _select.append( $('<option value="0">'+App.translations.get('wholeWorld')+'</option>'));
        }else{
            _select.append( $('<option value="--">..</option>'));
        }

        for(var _i in _items){
            _select.append( $('<option value="'+ _items[_i].geoId +'">'+ _items[_i].name +'</option>'));
        }

        // highlight selected
        if(childId){
            _select.find('[value='+childId+']').prop('selected', 'selected');
        }else{
            self._selectFirstElement();
        }

    },

    // service function, search in data holder
    _getChild: function(parentId, childId){
        var self = this;
        //self._log('_getChild(parentId, childId)', parentId, childId);

        parentId = ( parentId ? parseInt(parentId) : 0 );
        childId = ( childId ? parseInt(childId) : false );

        if(childId){

            if(!self._data[parentId]){
                self._log('_getChild() confused - no item ' + parentId);
                return false;
            }

            for(var _i in self._data[parentId]){
                if(childId == self._data[parentId][_i].geoId){
                    return self._data[parentId][_i];
                }
            }

        }

        self._log('_getChild() confused - no item ' + childId);
        return false;
    },

    // service function, search in data holder
    _getParentByChild: function(childId){
        var self = this;
        //self._log('_getParentByChildId(childId)', childId);

        childId = ( childId ? parseInt(childId) : 0 );

        if(childId){
            // find for parent
            for(var _parentId in self._data){
                if(self._getChild(_parentId, childId)){
                    return _parentId;
                }
            }
        }else{
            // check for root
            if(self._data[0]){
                return 0;
            }
        }

        self._log('_getParentByChild() confused - no item ' + childId);
        return false;
    },

    // get data by ajax
    _ajaxByChild: function(childId){
        var self = this;
        if(!childId || '0'==childId){
            self._log('_ajaxByChild() confused - no childId');
            return;
        }
        self._log('_ajaxByChild(childId)', childId);

        // get data by ajax
        ajaxPost(self._ajaxUrlForChild, {
            id: childId,
            onSuccess: function (ajaxResponse) {
                if (ajaxResponse && ajaxResponse.data) {

                    for(var _parentId in ajaxResponse.data){
                        self._data[_parentId] = ajaxResponse.data[_parentId]; // update data
                    }

                    var parentId = self._getParentByChild(childId);
                    if(false !== parentId){
                        // the show must go on
                        self.show({
                            parent: parentId,
                            id: childId
                        });
                    }else{
                        self._log('_ajaxByChild() confused - no result');
                    }
                }

            }
        });
    },

    // for step down
    _ajaxByParent: function(parentId, childId){
        var self = this;
        self._log('_ajaxByParent(parentId, childId)', parentId, childId);

        parentId = ( parentId ? parseInt(parentId) : 0 );
        childId = ( childId ? parseInt(childId) : 0 );

        // get data by ajax, then continue
        ajaxPost(self._ajaxUrlForParent, {
            id: parentId,
            onSuccess: function (ajaxResponse) {

                if (ajaxResponse && ajaxResponse.data) {


                    for(var _parentId in ajaxResponse.data){
                        self._data[_parentId] = ajaxResponse.data[_parentId]; // update data
                    }

                    if(self._data[parentId]){
                        //  vn
                        // Geo.theNewHeyss = self._data[parentId];
                        // console.log("Geo.theNewHeyss", self._data[parentId]);
                        
                        // the show must go on
                        self.show({
                            parent: parentId,
                            id: childId
                        });
                    }else{
                        self._log('_ajaxByParent() confused - no result');
                    }
                }

            }
        });
    },

    // for init
    _getDialog: function(){
        var self = this;
        //self._log('_getDialog()');

        // get template or created one
        var _dialog = $(self._dialogId);
        if(!_dialog.length){
            self._log('_getDialog() confused - no template', self._dialogId);
            return false;
        }
		var tempBox = $('#geo-hidden-keeper');
		if(!tempBox.length){
			tempBox = $('<div id="geo-hidden-keeper" class="hidden"></div>').appendTo($('body'));
		}
		else{
			tempBox.children().addClass('confirm-dialog').appendTo($('body'));
		}

        // create dialog if not yet
        if(!_dialog.parent().hasClass('ui-dialog')){
            self._log('_getDialog()', 'OPEN');
            var geoDialog = _dialog.dialog({
                modal: true,
                width: 450,
                dialogClass: self._dialogClasses,
				close: function(){
					_dialog.parent().removeClass('confirm-dialog').appendTo(tempBox);
                    enable_scroll();

				},
                open: function() {
                    disable_scroll();
                }
            });

            _dialog = $(self._dialogId);
            if(!_dialog.parent().hasClass('ui-dialog')){
                self._log('_getDialog() confused - no dialog', self._dialogId);
                return false;
            }

            self._bindEvents(_dialog); // after creating
        }

        return _dialog;
    },

    _selectFirstElement: function(){
        this._selectList.find('option:first').prop('selected', 'selected');
        this._selectedId = this._selectList.val();
        this._setTitle();
    },

    _selectLastElement: function(){
        this._selectList.find('option:last').prop('selected', 'selected');
        this._selectedId = this._selectList.val();
        this._setTitle();
    },

    _stopUselessEvents: function(e){
        e.stopPropagation();
        e.preventDefault();
    },

    // for init
    _bindEvents: function(_dialog){
        var self = this;
        //self._log('_bindEvents(_dialog)');

        self._backBtn = _dialog.find('.back');
        self._nextBtn = _dialog.find('.next');
        self._selectList = _dialog.find('select');
        self._endBtn = _dialog.find('.end');

        self._backBtn.unbind('click.geo').bind('click.geo', function(e){
            e.stopPropagation();

            self._log('_backBtn - click', self._selectedParentId);

            if(!self._selectedParentId || '0' == self._selectedParentId){
                self._selectFirstElement();
                //self._backBtn.hide(); // hide useless btn
                return; // no parent - nowhere to go
            }

            // go to parent
            self.show({
                parent: self._getParentByChild(self._selectedParentId),
                id: self._selectedParentId
            });
        });

        self._nextBtn.unbind('click.geo').bind('click.geo', function(e){
            e.stopPropagation();

            var _selectedId = self._selectList.val();
            self._log('_nextBtn - click', _selectedId);

            if(!_selectedId){
                //self._nextBtn.hide(); // hide useless btn
                return; // not selected - nowhere to go
            }

            // go to child
            self.show({
                parend: _selectedId,
                id: 0
            });

        });

        self._selectList.on('click', 'option', function(e){
            e.stopPropagation();

            var _selectedId = self._selectList.val();
            self._log('_selectList - click', _selectedId);

            if(self._selectedId == _selectedId){
                if('3' === _selectedId.toString()[0] || _selectedId == 0){ //
                    self._endBtn.trigger('click');
                }else if(_selectedId == '--'){
                    self._backBtn.trigger('click');
                }else{
                    // go to child
                    self.show({
                        parent: _selectedId,
                        id: 0
                    });
                }
            }else{

                //self._nextBtn.show();
                self._selectedId = _selectedId; // select item

                // show title for _selectedId
                self._setTitle();
            }
        });

        self._endBtn.unbind('click.geo').bind('click.geo', function(e){
            e.stopPropagation();

            var _dialog = self._getDialog();
            self._log('_endBtn - click', self._selectedId);

            if(!self._selectedId){
                //self._endBtn.hide(); // hide useless btn
                return; // not selected - nowhere to go
            }else if(self._selectedId == '--'){
                self._backBtn.trigger('click');
                return;
            }

            // save selection
            if(self.onSelect){
                var _item = self._getChild(self._selectedParentId, self._selectedId);
                var left = _item.leftIdx;
                var right = _item.rightIdx;
                if(self._selectedId == 0){
                    left = 0;
                    right = 0;
                }

                self.onSelect(self._selectedId, _dialog.dialog('option','title'), left, right );
            }

            _dialog.dialog('close');
        });

        self._selectList.unbind('keydown.geo').bind('keydown.geo', function(e){

            var current = self._selectList.find(":selected");
            switch(e.keyCode){
                case 40: // down arrow
                    self._stopUselessEvents(e);

                    if(current.length != 0 && current.next().length != 0){
                        current.prop('selected', '');
                        current.next().prop('selected', 'selected');
                    }

                    self._selectedId = self._selectList.val();
                    self._setTitle();
                break;
                case 38: // up arrow
                    self._stopUselessEvents(e);

                    if(current.length != 0 && current.prev().length != 0){
                        current.prop('selected', '');
                        current.prev().prop('selected', 'selected');
                    }

                    self._selectedId = self._selectList.val();
                    self._setTitle();
                break;
                case 39: // right arrow
                    self._stopUselessEvents(e);

                    self._selectedId = self._selectList.val();
                    self._selectList.find(":selected").trigger('click');
                break;
                case 37: // left arrow
                    self._backBtn.trigger('click.geo');
                break;
                case 36: // home
                    self._stopUselessEvents(e);
                    self._selectFirstElement();
                break;
                case 35: // end
                    self._stopUselessEvents(e);
                    self._selectLastElement();
                break;
                case 13: // enter
                    self._stopUselessEvents(e);
                    self._endBtn.trigger('click.geo');
                break;
            }

        });

        self._selectList.unbind('change.geo').bind('change.geo', function(e){
           self._selectedId = self._selectList.val();
           self._setTitle();
        });

    },

    // setup title for dialog header
    _setTitle: function(){
        var self = this;
        self._log('_setTitle()');

        var _parent = self._selectedParentId, _child = self._selectedId;
        var _title = ''; // default suffix

        if(_child == 0 && _parent == 0){
            _title = App.translations.get('wholeWorld');
        }else{

            if((!_child || _child == '--') && _parent){

                _child = _parent;
                _parent = self._getParentByChild(_child);
            }

            if(_child){
                do{
                    if(_title.length) _title = ', ' + _title;
                    _title = self._getChild(_parent, _child).name + _title;
                    _child = _parent;
                    _parent = self._getParentByChild(_child);
                } while(_parent); // repeat till root
            }

        }

        var _dialog = self._getDialog();

        if(!_title){
            // set default value from template, if exists, need for multylanguage support
            var _emptyTpl = _dialog.find('.no-title');
            if(_emptyTpl && _emptyTpl.length) _title = _emptyTpl.text();
        }

        _dialog.dialog({
            title: _title
        });

        return _title;
    },

    // old code

    initilized: false, // trigger if Geo module was initialized or not

    urlForChild: '/index/ajax-geo-child', // url for load chil nodes

    urlForParent: '/index/ajax-geo-parent', // url for load parent nodes

    urlForOneParent: '/index/ajax-geo-parent-one', // url for load one parent

    urlForFullName: '/index/ajax-geo-get-full-name', // url for load one parent

    dialogElm: '#geoDialog', // dialog HTML-block selector, must be one one DOM

    countryId : null, // current country id

    regoinId : null, // current regoin id

    cityId : null, // current city id

    current : null, // current id,

    fullName : null, // node full name text,

    title: 'Select country',

    /**
     * Initialization - find cached $ object, start event listening
     * @no params, no return
     */
    init: function (){
        this._log('init()');

        this.dialog = $(this.dialogElm);

        this.select = this.dialog.find('select');

        this._startListen();

        this.initilized = true;

    },

    /**
     * start event listening during the initialization
     * @no params, no return
     */
    _startListen: function(){
        this._log('_startListen()');

        var self = this;

        // navigation buttons
        this.dialog.find('.back').unbind('click.geo').bind('click.geo', function(){
            self._prevStep()
        });
        this.dialog.find('.end, option').unbind('click.geo').bind('click.geo', function(){
            self._nextStep()
        });
        console.log(this.select);
        // ckick in the list
        this.select.unbind('click.geo').bind('click.geo', function(){
            self._nextStep();
        }).unbind('dblclick.geo').bind('dblclick.geo', function(){
            self._nextStep();
        });

        // key press in the list
        /*this.select.unbind('keyup.geo').bind('keyup.geo', function(e){
            if (37 == e.keyCode && !this.countryId) { // arrow left
                $(this).parent().find('.back').trigger('click.geo');
            }
            if (39 == e.keyCode || 13 == e.keyCode) { // arrow right and enter key
                $(this).trigger('click.geo');
            }
        });*/

    },

    /**
     * Actions on click button 'previous' event
     * @no params, no return
     */
    _prevStep: function(){
        this._log('_prevStep()');

        var titleArray = this.title.split(',');

        if(this.countryId){

            var _firstInList = this.dialog.find('option').first(), _id = parseInt( _firstInList.val() );

            this.loadParentItems(_id);

        }

        if(this.cityId){

            this.cityId = null;
            this.regoinId = null;

            this.title = titleArray.pop();

        }else if(this.regoinId){

            this.regoinId = null;
            this.countryId = null;

            this.title = titleArray.pop();

        }else if(this.countryId){

            this.countryId = null;
            this.title = 'Select country';

        }

        this.select.focus();
    },

    /**
     * Actions on click button 'next' event
     * @no params, no return
     */
    _nextStep: function(){
        this._log('_nextStep()');

        var id = parseInt(this.select.val());

        var title = this.select.find('option:selected').text();

        if(id > 0){

            if(this.regoinId){

                this.cityId = id;
                this.finish();

                this.title = title + ', ' + this.title;

            }else if(this.countryId){

                this.regoinId = id;
                this.loadChildItems(id);

                this.title = title + ', ' + this.title;

            }else{

                this.countryId = id;
                this.loadChildItems(id);

                this.title = title;

            }

        }

        this.select.focus();
    },

    /**
     * Start point when calling dialog appears
     * @param Object
     * @return void
     */
    eventHandler: function(options){
        this._log('eventHandler(options)', options);

        if(this.initilized == false){

            this.init();

        }

        this.uniqId = options.uniqId;

        this.success = options.success;

        this.error = options.error;

        this.current = options.savedSelector.val();

        if(this.current && this.current != 0){

            this.cityId = this.current;

            this.loadOneParent(this.current);

        }else{

            this.loadChildItems(0);
        }

    },

    /**
     * Load on parent from server
     * @param int
     * @return void
     */
    loadOneParent: function(childId){
        this._log('loadOneParent(childId)', childId);

        var self = this;

        this.select.html('').addClass('ajax-loader');

        ajaxPost(this.urlForOneParent, {

            childId: childId,

            format: 'json',

            onSuccess: function(ajaxResponse) {

                if(ajaxResponse.result.level == '1'){

                    self.countryId = ajaxResponse.result.id;
                    self.regoinId  = childId;

                    self.loadChildItems(ajaxResponse.result.id, childId);

                }else if(ajaxResponse.result.level == '2'){

                    self.countryId = ajaxResponse.result.parentId;
                    self.regoinId  = ajaxResponse.result.id;
                    self.cityId    = childId;

                    self.loadChildItems(self.regoinId, childId);

                }

                self.title = ajaxResponse.result.nameFull;

            },

            onError: function(){

                self.errorHandler();

            }

        });

    },

    /**
     * Load child nodes from server
     * @param int, int
     * @return void
     */
    loadChildItems: function(parentId, selected){
        this._log('loadChildItems(parentId, selected)', parentId, selected);

        var self = this;

        this.select.html('').addClass('ajax-loader');

        ajaxPost(this.urlForChild, {

            parentId: parentId,

            onSuccess: function(ajaxResponse) {

                self.render(ajaxResponse.options, selected);

            },

            onError: function(){

                self.errorHandler();

            }

        });

    },

    /**
     * Load parent nodes from server
     * @param int, int
     * @return void
     */
    loadParentItems: function(childId, selected){
        this._log('loadParentItems(childId, selected)', childId, selected);

        var self = this;

        this.select.html('').addClass('ajax-loader');

        ajaxPost(this.urlForParent, {

            childId: childId,

            onSuccess: function(ajaxResponse) {

                self.render(ajaxResponse.options, selected);

            },

            onError: function(){

                self.errorHandler();

            }

        });

    },

    /**
     * Finishing when city been selected
     * @no params, no return
     */
    finish: function(){
        this._log('finish()');

        var self = this;

        this.select.html('').addClass('ajax-loader');

        ajaxPost(this.urlForFullName, {

            id: self.cityId,

            onSuccess: function(ajaxResponse) {

                self.fullName = ajaxResponse.result;

                self.dialog.dialog('close');

                self.successHandler();

                self.clearAll();

            },

            onError: function(){

                self.errorHandler();

            }

        });

    },

    /**
     * Clears all temporary vars
     * @no params, no return
     */
    clearAll: function(){
        this._log('clearAll()');

        this.uniqId = null;

        this.cityId = null;

        this.regoinId = null;

        this.countryId = null;

        this.success = null;

        this.error = null;

        this.current = null;

        this.fullName = null;

    },

    /**
     * Checks if the success callback setted and execute if is
     * @no params, no return
     */
    successHandler: function(){
        this._log('successHandler()');

        if(this.success){

            this.success(this.cityId, this.fullName);

        }

    },

    /**
     * Checks if the error callback setted and execute if is
     * @param string
     * @return void
     */
    errorHandler: function(errorText){
        this._log('errorHandler(errorText)', errorText);

        if(this.error){

            this.error(errorText);

        }

    },

    /**
     * Rendering dialog window
     * @param string, int
     * @return void
     */
    render: function(data, selected){
        this._log('render(data, selected)', data, selected);

        this.select.removeClass('ajax-loader').append(data);

        var title = this.title;
        this.dialog.dialog({
            modal: true,
            width: 450,
            title: title,
            dialogClass: 'dark-dialog without-btns close-outside'//confirm-dialog
        });

        if(selected){
            this._highlightSelected(selected);
        }

    },

    /**
     * Checks if is previously selected item and highlighted it
     * @param int
     * @return void
     */
    _highlightSelected: function(toSelect){
        if(!toSelect) return;
        this._log('_highlightSelected(toSelect)', toSelect);

        this.dialog.find('option').each(function(){

            var _item = parseInt($(this).val());

            if(_item == toSelect) {

                $(this).attr('selected', 'selected');
                $(this).focus();

                return;
            }

        });

    },

    findByKey: function(event){
        this._log('findByKey(event)', event);

    // todo: check is it obsolete
    },

    /**
     * Ajax request when autocomplete is
     * @param {string} text - autocomplete text to send
     * @param {Fuction} - success callback
     * @return void
     */
    postToAjaxGeoAutocomplete : function(text, response){
        ajaxPost('/index/ajax-geo-autocomplete', {
            name_startsWith: text,
            onSuccess: function(ajaxResponse) {
                if(response){
                    response( $.map(ajaxResponse.result, function(item) {
                        return {
                            id: item.id,
                            left: item.left,
                            right: item.right,
                            value: item.name,
                        }
                    }));
                }
            }
        });

    },

    /**
     * Actions after ajax autocomplete request was finished
     * @param {jQuery} elementIdTarget - $('#geoId')
     * @param {jQuery} leftIdTarget - $('#geoLef')
     * @param {jQuery} rightIdTarget - $('#geoRight')
     * @param {Object} item - selected item
     * @return void
     */
    autocompleteSelected: function(elementIdTarget, leftIdTarget, rightIdTarget, item){

        elementIdTarget.val(item.id);

        if(leftIdTarget){
            leftIdTarget.val(item.left);
        }
        if(rightIdTarget){
            rightIdTarget.val(item.right);
        }

        elementIdTarget.change();

    }

}

$.fn.geoAutocomplete = function(elementIdTarget, leftIdTarget, rightIdTarget){
    var context = this;

    this.autocomplete({
        source: function( request, response ) {
            if(Geo.useAutocomplete === true ){
                Geo.postToAjaxGeoAutocomplete(request.term, response);
            }
        },

        select: function(event, ui) {
            if(Geo.useAutocomplete === true ){
                Geo.autocompleteSelected(elementIdTarget, leftIdTarget, rightIdTarget, ui.item);
                context.removeClass('ui-autocomplete-loading');
            }
        },

        minLength: 2

    });

    this.keypress(function(event) {
        if(event.which == 13){
            Geo.useAutocomplete = false;
            context.autocomplete('close');
            context.addClass('ui-autocomplete-loading');

            Geo.postToAjaxGeoAutocomplete($(this).val(), function(result){
                if(result.length > 0){
                    Geo.autocompleteSelected(elementIdTarget, leftIdTarget, rightIdTarget, result[0]);
                    context.val(result[0].value);
                }else{
                    // nothing to do
                }

                Geo.useAutocomplete = true;
                context.removeClass('ui-autocomplete-loading');
            });
        }
    });

}