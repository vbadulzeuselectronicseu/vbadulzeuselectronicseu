/*
 * Global var App - contains all global manipulation and can be used to coordinate modules
 */

var standartCat = [1,2,3,4,5,6,7,8,9,10,11,12];

var App = {

    favoriteCats: standartCat, 

    editableFavoriteCats: false,

    translations: new Translations(), // transaltion model

    router : new Workspace(), // router class

    header: new Header(),

    friends: new Users(),

    chatbox: new ChatBox(),

    newMessages: new Messages(),

    messagesDispatcher: new MessagesDispatcher(),

    startUnreadCount: 0,

    visitor: new User(),

    notificationManager: new NotificationManager(),

    synchronizer: new Synchronizer(),

    popups: {

        prePost: new PrePost()
    },

    pages: {

        index:  new IndexPage(),
        top:    new TopPage(),
        urgent: new UrgentPage(),
        main:   new MainPage(),
        search: new SearchPage(),
        news:   new NewsPage(),
        post:   new PostPage()

    },

    /**
	 * Initialization
	 * @param void
	 * @return void
	 */
    init: function(){

        this.synchronizer.start();
        this.startHistoryInCabinetPage();

        this.header.start();
        this.messagesDispatcher.set({ totalUnreadCount: this.startUnreadCount });

        this.chatbox.checkEntity();

        Backbone.emulateHTTP = true;
        Backbone.emulateJSON = true;

        App.visitor.listenTo(App.synchronizer.get('changedLikes'), 'add', this.changedLikesHandler);
        this.notificationManager.start();
        

    },

    newEventDetected: function(data){

        App.messagesDispatcher.newEvent(data);

    },

    unreadCountChanged: function(data){

        App.messagesDispatcher.unreadCountChanged(data);

    },

    changedLikesHandler: function(event){

        if(event.get('initiator').get('id') == App.visitor.get('id')){
            App.visitor.get('likes').add(event.get('target'));
        }
    },

    startHistoryInCabinetPage: function(){

        var hrefArray = window.location.pathname.split('/');

        var currentUrl = hrefArray.pop();

        if(currentUrl == ''){

            currentUrl = hrefArray.pop();
        }

        if(currentUrl == 'user'){

            if(!this.cabinet){

                this.cabinet = Cabinet;
            }

            this.cabinet.startBindingMenuEvents();

            Backbone.history.start();

        }

    },

    changeUserAbout: function(data){

        if(window.userOnUsersPage){
            userOnUsersPage.get(data.id).set({ about: data.about });
        }
    },

    prepareDate: function(timestamp){

        var dateObj = new Date(timestamp * 1000 );
        
        var day     = dateObj.getDate();
        var month   = dateObj.getMonth()+1; // '+1' bacause JS getMonth returns int bettween 0 and 11
        var year    = dateObj.getFullYear();
        var hours   = dateObj.getHours();
        var minutes = dateObj.getMinutes();

        var today = new Date();
        var yesterday = new Date(); 
        yesterday.setDate(yesterday.getDate() - 1);

        var date = '';
        var time = '';

        minutes = minutes < 10 ? '0'+minutes : minutes;

        if (App.lang == 'ru') {
            time = hours + ':' + minutes;
        }else{ 
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'

            time = hours + ':' + minutes + ' ' + ampm
        }   

        if(day == today.getDate() && month == today.getMonth()+1 && year == today.getFullYear()){
            date = App.translations.get('Today') + ' ' + App.translations.get('at');
        }else if(day == yesterday.getDate() && month == yesterday.getMonth()+1 && year == yesterday.getFullYear()){
            date = App.translations.get('Yesterday')+ ' ' + App.translations.get('at');
        }else{
            month = month < 10 ? '0'+month : month;
            day = day < 10 ? '0'+day : day;
            year = new String(year).substr(2);

            if (App.lang == 'ru') { // format = 'dd.MM.YY H:mm';
                date = day + '.' + month + '.' + year;
            }else{// format = 'MM/dd/YY hh:mm a';
                date = month + '/' + day + '/' + year;
            }
        }

        return { date: date, time: time };
    }

};

$(function(){

    App.init();

})