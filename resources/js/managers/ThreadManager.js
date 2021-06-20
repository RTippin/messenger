window.ThreadManager = (function () {
    var opt = {
        INIT : false,
        ORIGINAL_ARG : null,
        SETUP : true,
        API : Messenger.common().API,
        thread : {
            id : null,
            type : null,
            name : null,
            admin : false,
            pending : false,
            muted : false,
            replying : false,
            reply_to_id : null,
            awaiting_my_approval : false,
            created_at : null,
            messages_unread : false,
            click_to_read : false,
            messaging : true,
            can_call : true,
            lockout : false,
            thread_history : true,
            history_id : null,
            history_route : null,
            history_loading : false,
            initializing : false,
            _id : null,
            _thread : null,
        },
        states : {
            lock : true,
            load_in_retries : 0,
            state_lockout_retries : 0,
            thread_filtered : false,
            thread_filter_search : null,
            messenger_search_term : null
        },
        socket : {
            online_status_setting : 1,
            chat : null,
            socket_retries : 0,
            send_typing : 0,
            is_away : false
        },
        storage : {
            active_profiles : [],
            who_typing : [],
            threads : [],
            messages : [],
            participants : [],
            pending_messages : [],
            temp_data : null
        },
        timers : {
            recent_bobble_timeout : null,
            socket_interval : null,
            remove_typing_interval : null,
            private_bobble_refresh_timeout : null,
            bobble_refresh_interval : null,
            drag_drop_overlay_hide : null
        },
        elements : {
            nav_search_link : $(".nav-search-link"),
            my_avatar_area : $("#my_avatar_status"),
            thread_area : $("#messages_ul"),
            message_container : $("#message_container"),
            message_sidebar_container : $("#message_sidebar_container"),
            socket_error_msg : $("#socket_error"),
            thread_search_input : $("#thread_search_input"),
            thread_search_bar : $("#threads_search_bar"),
            drag_drop_zone : $('#drag_drop_overlay'),
            messenger_search_input : null,
            messenger_search_results : null,
            msg_panel : null,
            doc_file : null,
            record_audio_message_btn : null,
            add_emoji_btn : null,
            data_table : null,
            message_text_input : null,
            form : null,
            the_thread : null,
            msg_stack : null,
            pending_msg_stack : null,
            new_msg_alert : null,
            reply_message_alert : null,
        }
    },
    mounted = {
        Initialize : function(arg) {
            if(!Messenger.common().modules.includes('ThreadTemplates')){
                setTimeout(function () {
                    mounted.Initialize(arg)
                }, 0);
                return;
            }
            opt.states.lock = false;
            if(!opt.ORIGINAL_ARG){
                opt.ORIGINAL_ARG = arg;
            }
            if("online_status_setting" in arg) opt.socket.online_status_setting = arg.online_status_setting;
            if("messaging" in arg) opt.thread.messaging = arg.messaging;
            if("lockout" in arg) opt.thread.lockout = arg.lockout;
            if("admin" in arg) opt.thread.admin = arg.admin;
            if("awaiting_my_approval" in arg) opt.thread.awaiting_my_approval = arg.awaiting_my_approval;
            if("pending" in arg) opt.thread.pending = arg.pending;
            if("can_call" in arg) opt.thread.can_call = arg.can_call;
            if("setup" in arg && "thread_id" in arg && arg.type === 0){
                mounted.setupOnce();
                LoadIn.initiate_thread({thread_id : arg.thread_id});
                return;
            }
            if("setup" in arg && arg.type === 3){
                mounted.setupOnce();
                LoadIn.createPrivate({
                    id : arg.id,
                    alias : arg.alias
                });
                return;
            }
            opt.INIT = true;
            PageListeners.listen().disposeTooltips();
            opt.thread.type = arg.type;
            if([1,2,3,4].includes(arg.type)){
                opt.elements.message_text_input = $("#message_text_input");
                opt.elements.form = $("#thread_form");
                opt.elements.new_msg_alert = $("#new_message_alert");
                opt.elements.reply_message_alert = $("#reply_message_alert");
                opt.elements.msg_panel = $(".chat-body");
                opt.elements.doc_file = $("#doc_file");
                opt.elements.record_audio_message_btn = $("#record_audio_message_btn");
                opt.elements.add_emoji_btn = $("#add_emoji_btn");
            }
            if([1,2,3].includes(arg.type)){
                if(arg.type === 3) opt.storage.temp_data = arg.temp_data;
                mounted.startWatchdog()
            }
            if(arg.type === 4) mounted.startWatchdog();
            if(arg.type === 5 && !Messenger.common().mobile) opt.elements.message_container.html(ThreadTemplates.render().empty_base());
            if(arg.type === 7){
                opt.elements.msg_panel = $(".chat-body");
                opt.elements.messenger_search_results = $("#messenger_search_content");
                opt.elements.messenger_search_input = $("#messenger_search_profiles");
                mounted.startWatchdog()
            }
            if('thread_id' in arg){
                opt.thread.id = arg.thread_id;
                opt.thread.name = arg.t_name;
                opt.elements.the_thread = $('#msg_thread_'+arg.thread_id);
                opt.elements.msg_stack = $('#messages_container_'+arg.thread_id);
                opt.elements.pending_msg_stack = $("#pending_messages");
                opt.thread.initializing = false;
                opt.thread._id = null;
                if(arg.type !== 3) methods.initializeRecentMessages();
            }
            Health.checkConnection();
            if('setup' in arg) mounted.setupOnce();
            PageListeners.listen().tooltips()
        },
        setupOnce : function(){
            if(!opt.SETUP) return;
            let elm = document.getElementById('message_container');
            LoadIn.threads();
            setInterval(function(){
                if(!NotifyManager.sockets().forced_disconnect) LoadIn.threads()
            }, 300000);
            if(opt.thread.type === 5) window.history.replaceState({type : 5}, null, Messenger.common().WEB);
            window.onpopstate = function(event) {
                if(event.state && "type" in event.state && !opt.states.lock){
                    switch(event.state.type){
                        case 1:
                        case 2:
                            LoadIn.initiate_thread({thread_id : event.state.thread_id}, true);
                        break;
                        case 3:
                            LoadIn.createPrivate({alias : event.state.alias, id : event.state.id}, true);
                        break;
                        case 4:
                            LoadIn.createGroup(true);
                        break;
                        case 5:
                            LoadIn.closeOpened();
                        break;
                        case 6:
                            LoadIn.contacts(true);
                        break;
                        case 7:
                            LoadIn.search(true);
                        break;
                    }
                }
                else{
                    return false;
                }
            };
            opt.elements.thread_search_input.on("keyup mouseup", methods.checkThreadFilters);

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                elm.addEventListener(eventName, methods.fileDragDrop, false)
            });
            if(opt.elements.nav_search_link.length) opt.elements.nav_search_link.click(mounted.searchLinkClicked);
            setInterval(mounted.timeAgo, 10000);
            opt.SETUP = false;
        },
        reset : function(lock){
            mounted.stopWatchdog();
            if(opt.socket.chat) opt.socket.chat.unsubscribe();
            if(opt.timers.remove_typing_interval) clearInterval(opt.timers.remove_typing_interval);
            if(opt.timers.socket_interval) clearInterval(opt.timers.socket_interval);
            if(opt.timers.recent_bobble_timeout) clearTimeout(opt.timers.recent_bobble_timeout);
            if(opt.timers.bobble_refresh_interval) clearInterval(opt.timers.bobble_refresh_interval);
            if(opt.timers.private_bobble_refresh_timeout) clearTimeout(opt.timers.private_bobble_refresh_timeout);
            if(opt.timers.drag_drop_overlay_hide){
                clearTimeout(opt.timers.drag_drop_overlay_hide);
                opt.elements.drag_drop_zone.addClass('NS');
            }
            opt.elements.message_container.removeClass('msg-ctnr-unread');
            opt.elements.thread_area.find('.thread_list_item').removeClass('alert-warning shadow-sm rounded');
            opt.elements.thread_area.find('.thread-group-avatar').removeClass('avatar-is-online').addClass('avatar-is-offline');
            PageListeners.listen().disposeTooltips();
            opt = Object.assign({}, opt, {
                thread : {
                    id : null,
                    type : null,
                    name : null,
                    admin : false,
                    pending : false,
                    muted : false,
                    replying : false,
                    reply_to_id : null,
                    awaiting_my_approval : false,
                    created_at : null,
                    messages_unread : false,
                    click_to_read : false,
                    messaging : true,
                    can_call : true,
                    lockout : false,
                    thread_history : true,
                    history_id : null,
                    history_route : null,
                    history_loading : false,
                    initializing : false,
                    _id : null,
                    _thread : null,
                },
                states : {
                    lock : lock,
                    load_in_retries : 0,
                    state_lockout_retries : 0,
                    thread_filtered : opt.states.thread_filtered,
                    thread_filter_search : opt.states.thread_filter_search,
                    messenger_search_term : null
                },
                socket : {
                    online_status_setting : opt.socket.online_status_setting,
                    chat : null,
                    socket_retries : 0,
                    send_typing : 0,
                    is_away : false
                },
                storage : {
                    active_profiles : [],
                    who_typing : [],
                    threads : opt.storage.threads,
                    messages : [],
                    participants : [],
                    pending_messages : [],
                    temp_data : null
                },
                timers : {
                    recent_bobble_timeout : null,
                    socket_interval : null,
                    remove_typing_interval : null,
                    private_bobble_refresh_timeout : null,
                    bobble_refresh_interval : null,
                    drag_drop_overlay_hide : null
                },
                elements : {
                    nav_search_link : opt.elements.nav_search_link,
                    my_avatar_area : opt.elements.my_avatar_area,
                    thread_area : opt.elements.thread_area,
                    message_container : opt.elements.message_container,
                    message_sidebar_container : opt.elements.message_sidebar_container,
                    socket_error_msg : opt.elements.socket_error_msg,
                    thread_search_input : opt.elements.thread_search_input,
                    thread_search_bar : opt.elements.thread_search_bar,
                    drag_drop_zone : opt.elements.drag_drop_zone,
                    messenger_search_input : null,
                    messenger_search_results : null,
                    msg_panel : null,
                    doc_file : null,
                    record_audio_message_btn : null,
                    data_table : null,
                    message_text_input : null,
                    form : null,
                    the_thread : null,
                    msg_stack : null,
                    pending_msg_stack : null,
                    new_msg_alert : null,
                    reply_message_alert : null,
                    add_emoji_btn : null,
                }
            })
        },
        timeAgo : function(){
            $("time.timeago").each(function () {
                $(this).html(Messenger.format().makeTimeAgo($(this).attr('datetime')))
            });
        },
        startWatchdog : function(){
            switch(opt.thread.type){
                case 1:
                case 2:
                    if(!opt.thread.lockout && opt.thread.messaging) opt.elements.message_text_input.prop('disabled', false);
                    opt.timers.remove_typing_interval = setInterval(methods.removeTypers, 1000);
                    opt.timers.bobble_refresh_interval = setInterval(function() {
                        if(!NotifyManager.sockets().forced_disconnect) LoadIn.bobbleHeads()
                    }, 180000);
                    opt.elements.msg_panel.click(mounted.msgPanelClick);
                    opt.elements.msg_panel.scroll(mounted.msgPanelScroll);
                    opt.elements.doc_file.change(mounted.documentChange);
                    opt.elements.record_audio_message_btn.click(mounted.audioMessage);
                    opt.elements.add_emoji_btn.click(mounted.showEmojiPicker);
                    opt.elements.message_text_input.on('paste', methods.pasteImage);
                    opt.elements.form.keydown(mounted.formKeydown);
                    opt.elements.form.on('input keyup', methods.manageSendMessageButton);
                    opt.elements.form.on('submit', mounted.stopDefault);
                    opt.elements.new_msg_alert.click(mounted.newMsgAlertClick);
                    opt.elements.reply_message_alert.click(methods.resetReplying);
                    opt.elements.message_container.click(mounted.clickMarkRead);
                    if(Messenger.common().mobile) opt.elements.message_text_input.click(mounted.inputClickScroll);
                    if(!Messenger.common().mobile) opt.elements.message_text_input.focus();
                break;
                case 3:
                    opt.elements.message_text_input.prop('disabled', false);
                    opt.elements.msg_panel.click(mounted.msgPanelClick);
                    opt.elements.doc_file.change(mounted.documentChange);
                    opt.elements.record_audio_message_btn.click(mounted.audioMessage);
                    opt.elements.add_emoji_btn.click(mounted.showEmojiPicker);
                    opt.elements.form.keydown(mounted.formKeydown);
                    opt.elements.form.on('input keyup', methods.manageSendMessageButton);
                    opt.elements.form.on('submit', mounted.stopDefault);
                    if(!Messenger.common().mobile) opt.elements.message_text_input.focus();
                break;
                case 4:
                    let subject = document.getElementById('subject');
                    opt.elements.msg_panel.click(mounted.msgPanelClick);
                    if(!Messenger.common().mobile) subject.focus();
                    PageListeners.listen().validateForms();
                break;
                case 7:
                    opt.elements.messenger_search_input.on("keyup mouseup", mounted.runMessengerSearch);
                    opt.elements.msg_panel.click(mounted.msgPanelClick);
                    opt.elements.messenger_search_input.focus();
                break;
            }
        },
        stopWatchdog : function(){
            switch(opt.thread.type){
                case 1:
                case 2:
                    try{
                        opt.elements.msg_panel.off('click', mounted.msgPanelClick);
                        opt.elements.msg_panel.off('scroll', mounted.msgPanelScroll);
                        opt.elements.doc_file.off('change', mounted.documentChange);
                        opt.elements.record_audio_message_btn.off('click', mounted.audioMessage);
                        opt.elements.add_emoji_btn.off('click', mounted.showEmojiPicker);
                        opt.elements.message_text_input.off('paste', methods.pasteImage);
                        opt.elements.form.off('keydown', mounted.formKeydown);
                        opt.elements.form.off('input keyup', methods.manageSendMessageButton);
                        opt.elements.form.off('submit', mounted.stopDefault);
                        opt.elements.new_msg_alert.off('click', mounted.newMsgAlertClick);
                        opt.elements.reply_message_alert.off('click', methods.resetReplying);
                        opt.elements.message_container.off('click', mounted.clickMarkRead);
                        if(Messenger.common().mobile) opt.elements.message_text_input.off('click', mounted.inputClickScroll);
                    }catch (e) {
                        console.log(e);
                    }
                break;
                case 3:
                    try{
                        opt.elements.msg_panel.off('click', mounted.msgPanelClick);
                        opt.elements.doc_file.off('change', mounted.documentChange);
                        opt.elements.record_audio_message_btn.off('click', mounted.audioMessage);
                        opt.elements.add_emoji_btn.off('click', mounted.showEmojiPicker);
                        opt.elements.form.off('keydown', mounted.formKeydown);
                        opt.elements.form.off('input keyup', methods.manageSendMessageButton);
                        opt.elements.form.off('submit', mounted.stopDefault);
                    }catch (e) {
                        console.log(e);
                    }
                break;
                case 4:
                    try{
                        opt.elements.msg_panel.off('click', mounted.msgPanelClick);
                    }catch (e) {
                        console.log(e);
                    }
                break;
                case 7:
                    try{
                        opt.elements.msg_panel.off('click', mounted.msgPanelClick);
                        opt.elements.messenger_search_input.off("keyup mouseup", mounted.runMessengerSearch);
                    }catch (e) {
                        console.log(e);
                    }
                break;
            }
        },
        stopDefault : function(e){
            e.preventDefault()
        },
        searchLinkClicked : function(e){
            mounted.stopDefault(e);
            $('body').click();
            LoadIn.search()
        },
        runMessengerSearch : function(e){
            if(opt.thread.type !== 7) return;
            let current_term = opt.states.messenger_search_term, time = new Date();
            if(e && e.type === 'mouseup'){
                setTimeout(mounted.runMessengerSearch, 0);
                return;
            }
            if(opt.elements.messenger_search_input.val().trim().length){
                if(opt.elements.messenger_search_input.val().trim().length >= 3){
                    if(current_term !== opt.elements.messenger_search_input.val().trim()){
                        opt.states.messenger_search_term = opt.elements.messenger_search_input.val().trim();
                        opt.elements.messenger_search_results.html(ThreadTemplates.render().loader());
                        Messenger.xhr().request({
                            route : Messenger.common().API + 'search/'+opt.states.messenger_search_term,
                            success : methods.manageMessengerSearch,
                            fail_alert : true
                        })
                    }
                }
                else{
                    opt.states.messenger_search_term = opt.elements.messenger_search_input.val().trim();
                    opt.elements.messenger_search_results.html(ThreadTemplates.render().thread_empty_search(true));
                }
            }
            else{
                opt.states.messenger_search_term = null;
                opt.elements.messenger_search_results.html(ThreadTemplates.render().thread_empty_search());
            }
        },
        inputClickScroll : function(){
            setTimeout(function () {
                methods.threadScrollBottom(true, false)
            }, 200)
        },
        formKeydown : function(e){
            switch (opt.thread.type) {
                case 1:
                case 2:
                    if(opt.thread.lockout || !opt.thread.messaging) return;
                    if (e.keyCode === 13) {
                        methods.sendMessage();
                        methods.stopTyping();
                        return;
                    }
                    methods.isTyping();
                break;
                case 3:
                    if(e.keyCode === 13) new_forms.newPrivate(false);
                break;
            }
        },
        clickMarkRead : function(){
            if(opt.thread.click_to_read || methods.checkThreadStorageUnread()) methods.markRead()
        },
        msgPanelClick : function(e){
            if(opt.thread.type === 7){
                let focus_input = document.getElementById('messenger_search_profiles');
                Messenger.format().focusEnd(focus_input);
                return;
            }
            let focus_input = document.getElementById('message_text_input');
            switch (opt.thread.type) {
                case 1:
                case 2:
                    if(opt.thread.lockout || !opt.thread.messaging) return;
                    let elm_class = $(e.target).attr('class');
                    let ignore = [
                        'message-text',
                        'message-text pt-2',
                        'fas fa-trash',
                        'fas fa-grin',
                        'dropdown-item',
                        'fas fa-ellipsis-v',
                        'fas fa-grin-tongue',
                        'fas fa-reply',
                        'fas fa-pen',
                        'joypixels',
                        'ml-1 font-weight-bold text-primary',
                        'badge badge-light mr-1 px-1 pointer_area',
                        'reacted-by-me badge badge-light mr-1 px-1 pointer_area'
                    ];
                    if (ignore.includes(elm_class) || Messenger.common().mobile) return;
                    Messenger.format().focusEnd(focus_input);
                break;
                case 3:
                    if(!opt.thread.messaging) return;
                    Messenger.format().focusEnd(focus_input);
                break;
                case 4:
                    if(e.target.id === 'msg_thread_new_group') Messenger.format().focusEnd(document.getElementById('subject'));
                break;
            }
        },
        msgPanelScroll : function(){
            if($(this).scrollTop()  <= 500 ) methods.loadHistory();
            if(methods.threadScrollBottom(false, true) && opt.thread.messages_unread && !opt.socket.is_away && document.hasFocus()) methods.markRead()
        },
        newMsgAlertClick : function(){
            methods.threadScrollBottom(true, false);
            methods.markRead()
        },
        audioMessage : function(){
            if(opt.thread.lockout || !opt.thread.messaging) return;
            RecordAudio.open();
        },
        showEmojiPicker : function(){
            if(opt.thread.lockout || !opt.thread.messaging) return;
            EmojiPicker.addMessage()
        },
        documentChange : function(){
            switch (opt.thread.type) {
                case 1:
                case 2:
                    if(opt.thread.lockout || !opt.thread.messaging) return;
                    let input = document.getElementById('doc_file'), files = input.files;
                    ([...files]).forEach(methods.sendUploadFiles);
                    input.value = '';
                break;
                case 3:
                    if(!opt.thread.messaging) return;
                    Messenger.button().addLoader({id : '#file_upload_btn'});
                    new_forms.newPrivate(true);
                break;
            }
        },
        avatarListener : function(){
            $('.grp-img-check').click(function() {
                $('.grp-img-check').not(this).removeClass('grp-img-checked').siblings('input').prop('checked',false);
                $(this).addClass('grp-img-checked').siblings('input').prop('checked',true);
            });
            $("#avatar_image_file").change(function(){
                groups.updateGroupAvatar({action : 'upload'});
            });
        },
        switchToggleListener : function(){
            $(".switch_input").each(function(){
                if($(this).is(':checked')){
                    $(this).parents().closest('tr').addClass('table-warning');
                    return;
                }
                $(this).parents().closest('tr').removeClass('table-warning')
            })
        },
        startPresence : function(full){
            if(opt.thread.awaiting_my_approval || opt.thread.muted || opt.thread.lockout) return;
            if(full) opt.socket.chat = null;
            if(opt.socket.chat){
                opt.socket.chat.subscribe();
                return;
            }
            if(typeof NotifyManager.sockets().Echo.connector.channels['presence-messenger.thread.'+opt.thread.id] !== 'undefined'){
                NotifyManager.sockets().Echo.connector.channels['presence-messenger.thread.'+opt.thread.id].subscribe();
                opt.socket.chat = NotifyManager.sockets().Echo.connector.channels['presence-messenger.thread.'+opt.thread.id]
            }
            else{
                opt.socket.chat = NotifyManager.sockets().Echo.join('messenger.thread.'+opt.thread.id);
            }
            opt.socket.chat.here(function(users){
                opt.storage.active_profiles = [];
                $('.thread_error_area').hide();
                $.each(users, function() {
                    if(this.provider_id !== Messenger.common().id){
                        opt.storage.active_profiles.push(this);
                        methods.updateBobbleHead(this.provider_id, null)
                    }
                });
                methods.drawBobbleHeads();
                methods.sendOnlineStatus((opt.socket.is_away && opt.socket.online_status_setting !== 0 ? 2 : opt.socket.online_status_setting));
            })
            .joining(function(user) {
                opt.storage.active_profiles.push(user);
                if(opt.storage.messages.length) methods.updateBobbleHead(user.provider_id, opt.storage.messages[0].id);
                methods.drawBobbleHeads();
                methods.sendOnlineStatus((opt.socket.is_away && opt.socket.online_status_setting !== 0 ? 2 : opt.socket.online_status_setting));
                PageListeners.listen().tooltips()
            })
            .leaving(function(user) {
                methods.updateActiveProfile(user.provider_id, 3)
            })
            .listenForWhisper('typing', function(user){
                if(!opt.storage.messages.length) return;
                if(!user.typing){
                    methods.removeTypers(user.provider_id);
                    return;
                }
                let time = new Date(),
                found = opt.storage.who_typing.filter( function(el) {
                    return el.includes( user.provider_id );
                });
                if(!found.length){
                    opt.storage.who_typing.push([user.provider_id, user.name, time.getTime()]);
                    methods.addTypers();
                    return;
                }
                found[0][2] = time.getTime();
            })
            .listenForWhisper('online', function(user){
                methods.threadOnlineStatus((user.online_status));
                methods.updateActiveProfile(user.provider_id, user.online_status)
            })
            .listenForWhisper('read', function(message){
                methods.updateBobbleHead(message.provider_id, message.message_id);
                methods.drawBobbleHeads()
            })
            .listen('.thread.settings', methods.groupSettingsState)
            .listen('.thread.avatar', methods.groupAvatarState)
            .listen('.message.edited', methods.renderUpdatedMessage)
            .listen('.reaction.added', methods.updateNewReaction)
            .listen('.reaction.removed', methods.updateRemoveReaction)

        }
    },
    Health = {
        checkConnection : function(){
            if(!Messenger.common().modules.includes('NotifyManager') || !NotifyManager.sockets().status || !NotifyManager.sockets().Echo){
                if(opt.socket.socket_retries >= 10){
                    opt.storage.active_profiles = [];
                    opt.socket.socket_retries = 0;
                    Health.unreadCheck();
                    opt.elements.socket_error_msg.html(ThreadTemplates.render().socket_error());
                    if(opt.thread.id){
                        $('.thread_error_area').show();
                        $('.thread_error_btn').popover()
                    }
                }
                if(opt.timers.socket_interval === null){
                    opt.timers.socket_interval = setInterval(function() {
                        Health.checkConnection();
                    }, 1000);
                }
                opt.socket.socket_retries++;
                if(Messenger.common().modules.includes('NotifyManager') && NotifyManager.sockets().forced_disconnect) opt.elements.my_avatar_area.html(ThreadTemplates.render().my_avatar_status(0));
                return;
            }
            Health.onConnection()
        },
        onConnection : function(full){
            opt.elements.my_avatar_area.html(ThreadTemplates.render().my_avatar_status(opt.socket.online_status_setting));
            PageListeners.listen().tooltips();
            opt.socket.socket_retries = 0;
            opt.elements.socket_error_msg.html('');
            clearInterval(opt.timers.socket_interval);
            opt.timers.socket_interval = null;
            if(opt.thread.id && opt.thread.type !== 3){
                $('.thread_error_area').hide();
                mounted.startPresence(full)
            }
        },
        reConnected : function(full){
            opt.elements.my_avatar_area.html(ThreadTemplates.render().my_avatar_status(opt.socket.online_status_setting));
            PageListeners.listen().tooltips();
            Health.onConnection(full);
            if(!CallManager.state().initialized) LoadIn.threads();
            if(opt.thread.id){
                opt.storage.participants = [];
                methods.initializeRecentMessages(true);
            }
        },
        unreadCheck : function(){
            if(!Messenger.common().modules.includes('NotifyManager') || NotifyManager.sockets().forced_disconnect) return;
            let checkTotalUnread = function () {
                if(CallManager.state().initialized) return;
                Messenger.xhr().request({
                    route : Messenger.common().API+'unread-threads-count',
                    success : function(data){
                        if(NotifyManager.counts().threads !== data.unread_threads_count){
                            NotifyManager.updateMessageCount({total_unread : data.unread_threads_count});
                            LoadIn.threads()
                        }
                    },
                    fail : null
                })
            };
            if(opt.thread.id){
                Messenger.xhr().request({
                    route : Messenger.common().API+'threads/'+opt.thread.id+'/is-unread',
                    success : function(data){
                        if(data.unread){
                            opt.storage.participants = [];
                            if(document.hasFocus() && !opt.socket.is_away) methods.markRead();
                            methods.initializeRecentMessages(true);
                            if(!document.hasFocus() || opt.socket.is_away) NotifyManager.sound('message');
                        }
                        else{
                            checkTotalUnread()
                        }
                    },
                    fail : null
                });
                return;
            }
            checkTotalUnread()
        }
    },
    Imports = {
        newMessage : function(data){
            if(opt.thread.id === data.thread_id){
                methods.addMessage(data);
                return;
            }
            if(CallManager.state().initialized && CallManager.state().thread_id !== data.thread_id) return;
            if(opt.thread.initializing && opt.thread._id === data.thread_id){
                opt.storage.pending_messages.push(data);
                methods.updateThread(data, false, false, true);
                return;
            }
            methods.updateThread(data, false, false, true);
            if(Messenger.common().id !== data.owner_id) NotifyManager.sound('message')
        },
        audioMessage : function(audio){
            if(opt.thread.id){
                if(opt.thread.type === 3){
                    new_forms.newPrivate(false, true, audio);
                } else {
                    methods.sendUploadFiles(audio, false, true);
                }
            }
        },
        callStatus : function(data, action){
            methods.threadCallStatus(data, action)
        },
        addedToThread : function(thread_id){
            LoadIn.thread(thread_id);
            NotifyManager.sound('message')
        },
        promotedAdmin : function(thread_id){
            if(opt.thread.id === thread_id){
                NotifyManager.sound('notify');
                Messenger.alert().Alert({
                    title : 'You were promoted to admin. Refreshing the group...',
                    toast : true,
                    theme : 'info'
                });
                LoadIn.initiate_thread({thread_id : opt.thread.id, force : true, read : false})
            }
        },
        demotedAdmin : function(thread_id){
            if(opt.thread.id === thread_id){
                NotifyManager.sound('notify');
                Messenger.alert().Alert({
                    title : 'You were demoted from admin. Refreshing the group...',
                    toast : true,
                    theme : 'info'
                });
                LoadIn.initiate_thread({thread_id : opt.thread.id, force : true, read : false})
            }
        },
        permissionsUpdated : function(thread_id){
            if(opt.thread.id === thread_id){
                NotifyManager.sound('notify');
                Messenger.alert().Alert({
                    title : 'Your permissions were updated. Refreshing the group...',
                    toast : true,
                    theme : 'info'
                });
                LoadIn.initiate_thread({thread_id : opt.thread.id, force : true, read : false})
            }
        },
        threadApproval : function(thread_id, approved){
            if(approved){
                if(opt.thread.id === thread_id){
                    LoadIn.initiate_thread({thread_id : thread_id, force : true})
                }
                else{
                    LoadIn.threads();
                }
            }
            else{
                if(opt.thread.id === thread_id) LoadIn.closeOpened();
                setTimeout(function () {
                    methods.removeThread(thread_id)
                }, 2500)
            }
        },
        threadLeft : function(thread_id){
            if(opt.thread.id === thread_id) LoadIn.closeOpened();
            setTimeout(function () {
                methods.removeThread(thread_id)
            }, 2500)
        },
        purgeMessage : function(message){
            if(opt.thread.id === message.thread_id){
                methods.purgeMessage(message.message_id);
                $("#message_"+message.message_id).remove()
            }
        },
    },
    methods = {
        initiatePrivate : function(arg, data, noHistory){
            if(data.resources.hasOwnProperty('messages')){
                opt.storage.messages = data.resources.messages.data;
                if(!data.resources.messages.meta.final_page){
                    opt.thread.thread_history = true;
                    opt.thread.history_id = data.resources.messages.meta.next_page_id;
                    opt.thread.history_route = data.resources.messages.meta.next_page_route;
                }
                else{
                    opt.thread.thread_history = false;
                    opt.thread.history_id = null;
                    opt.thread.history_route = null;
                }
            }
            opt.storage.participants = data.resources.hasOwnProperty('participants') ? data.resources.participants.data : [];
            opt.elements.message_container.html(ThreadTemplates.render().render_private(data));
            if(!noHistory) window.history.pushState({type : 1, thread_id : data.id}, null, Messenger.common().WEB + '/'+data.id);
            opt.thread.created_at = data.created_at;
            opt.thread.muted = data.options.muted;
            opt.thread._thread = data;
            mounted.Initialize({
                type : data.type,
                thread_id : data.id,
                t_name : data.name,
                can_call : data.options.call,
                admin : data.options.admin,
                pending : data.pending,
                awaiting_my_approval : data.options.awaiting_my_approval ?? false,
                messaging : data.options.message,
                lockout : data.locked
            });
            methods.updateThread(data, true, false, ('new' in arg))
        },
        initiateGroup : function(arg, data, noHistory){
            if(data.resources.hasOwnProperty('messages')){
                opt.storage.messages = data.resources.messages.data;
                if(!data.resources.messages.meta.final_page){
                    opt.thread.thread_history = true;
                    opt.thread.history_id = data.resources.messages.meta.next_page_id;
                    opt.thread.history_route = data.resources.messages.meta.next_page_route;
                }
                else{
                    opt.thread.thread_history = false;
                    opt.thread.history_id = null;
                    opt.thread.history_route = null;
                }
            }
            opt.storage.participants = data.resources.hasOwnProperty('participants') ? data.resources.participants.data : [];
            opt.elements.message_container.html(ThreadTemplates.render().render_group(data));
            if(!noHistory) window.history.pushState({type : 2, thread_id : data.id}, null, Messenger.common().WEB + '/'+data.id);
            opt.thread.created_at = data.created_at;
            opt.thread.muted = data.options.muted;
            opt.thread._thread = data;
            mounted.Initialize({
                type : data.type,
                thread_id : data.id,
                t_name : data.name,
                can_call : data.options.call,
                admin : data.options.admin,
                messaging : data.options.message,
                lockout : data.locked
            });
            methods.updateThread(data, true, false, ('new' in arg))
        },
        manageMessengerSearch : function(search){
            if(opt.thread.type !== 7) return;
            if(!search.data.length){
                opt.elements.messenger_search_results.html(ThreadTemplates.render().thread_empty_search(true, true));
                return;
            }
            opt.elements.messenger_search_results.html('');
            search.data.forEach((profile) => {
                opt.elements.messenger_search_results.append(ThreadTemplates.render().messenger_search(profile))
            });
            LazyImages.update();
        },
        fileDragDrop : function(e){
            let isFile = function () {
                for (let i = 0; i < e.dataTransfer.items.length; i++){
                    if (e.dataTransfer.items[i].kind === "file") {
                        return true;
                    }
                }
                return false;
            };
            if(!isFile()) return;
            e.preventDefault();
            e.stopPropagation();
            if(![1,2].includes(opt.thread.type) || !opt.thread.id || opt.thread.lockout || !opt.thread.messaging) return;
            if(['dragenter', 'dragover'].includes(e.type)){
                if(opt.timers.drag_drop_overlay_hide) clearTimeout(opt.timers.drag_drop_overlay_hide);
                opt.elements.drag_drop_zone.fadeIn('fast');
            }
            if(e.type === 'dragleave'){
                opt.timers.drag_drop_overlay_hide = setTimeout(function () {
                    opt.elements.drag_drop_zone.fadeOut('fast')
                }, 200);
            }
            if(e.type === 'drop'){
                opt.elements.drag_drop_zone.fadeOut('fast');
                let files = e.dataTransfer.files;
                ([...files]).forEach(methods.sendUploadFiles);
                opt.elements.message_text_input.focus()
            }
        },
        manageSendMessageButton : function(){
            let btn = $("#inline_send_msg_btn"), message_contents = opt.elements.message_text_input.val();
            if(message_contents.trim().length){
                if(!btn.length){
                    opt.elements.message_text_input.after(ThreadTemplates.render().send_msg_btn(false))
                }
            }
            else{
                btn.remove()
            }
        },
        groupSettingsState : function(settings){
            if(Messenger.common().id !== settings.sender.provider_id){
                NotifyManager.sound('notify');
                Messenger.alert().Alert({
                    title : settings.sender.name+' updated the groups settings. Refreshing the group...',
                    toast : true,
                    theme : 'info'
                })
            }
            LoadIn.initiate_thread({thread_id : opt.thread.id, force : true, read : false})
        },
        groupAvatarState : function(settings){
            if(Messenger.common().id !== settings.sender.provider_id){
                NotifyManager.sound('notify');
                Messenger.alert().Alert({
                    title : settings.sender.name+' updated the groups avatar. Refreshing the group...',
                    toast : true,
                    theme : 'info'
                })
            }
            LoadIn.initiate_thread({thread_id : opt.thread.id, force : true, read : false})
        },
        threadScrollBottom : function(force, check){
            if(!opt.elements.the_thread) return false;
            let top = opt.elements.the_thread.prop("scrollTop"), height = opt.elements.the_thread.prop("scrollHeight"), offset = opt.elements.the_thread.prop("offsetHeight");
            if(force || top === (height - offset) || ((height - offset) - top) < 200){
                if(!check) opt.elements.the_thread.scrollTop(height);
                return true;
            }
            return false;
        },
        statusOnline : function(state, inactivity){
            opt.socket.is_away = (state === 2 && inactivity);
            if(opt.INIT && opt.elements.my_avatar_area.length){
                opt.elements.my_avatar_area.html(ThreadTemplates.render().my_avatar_status((state === 1 && opt.socket.online_status_setting === 2 ? 2 : (state === 1 && opt.socket.online_status_setting === 0 ? 0 : (state === 2 && opt.socket.online_status_setting === 0 ? 0 : state)))));
                PageListeners.listen().tooltips();
            }
            methods.sendOnlineStatus((state === 1 && opt.socket.online_status_setting === 2 ? 2 : state))
        },
        updateOnlineStatusSetting : function(state){
            opt.socket.online_status_setting = state;
            methods.statusOnline(state, false)
        },
        checkThreadStorageUnread : function(){
            if(!opt.thread.id) return false;
            let thread = methods.locateStorageItem({type : 'thread', id : opt.thread.id});
            return thread.found && opt.storage.threads[thread.index].unread;
        },
        markRead : function(){
            if(!opt.thread.id || opt.thread.awaiting_my_approval || !methods.threadScrollBottom(false, true)) return;
            opt.thread.messages_unread = false;
            opt.elements.message_container.removeClass('msg-ctnr-unread');
            opt.thread.click_to_read = false;
            opt.elements.new_msg_alert.hide();
            methods.updateThread({thread_id : opt.thread.id}, false, true, false);
            if(opt.storage.messages.length) methods.seenMessage(opt.storage.messages[0].id);
            Messenger.xhr().request({
                route : Messenger.common().API+'threads/'+opt.thread.id+'/mark-read',
                fail : null
            })
        },
        loadDataTable : function(elm, special){
            if(opt.elements.data_table) opt.elements.data_table.destroy();
            if(!elm || !elm.length) return;
            if(special){
                opt.elements.data_table = elm.DataTable({
                    "language": {
                        "info": "Showing _START_ to _END_ of _TOTAL_ friends",
                        "lengthMenu": "Show _MENU_ friends",
                        "infoEmpty": "Showing 0 to 0 of 0 friends",
                        "infoFiltered": "(filtered from _MAX_ total friends)",
                        "emptyTable": "No friends found",
                        "zeroRecords": "No matching friends found"
                    },
                    "drawCallback": function(settings){
                        let api = new $.fn.DataTable.Api(settings), pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                        pagination.toggle(api.page.info().pages > 1);
                        LazyImages.update();
                    },
                    "pageLength": 100
                });
                return;
            }
            opt.elements.data_table = elm.DataTable({
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ participants",
                    "lengthMenu": "Show _MENU_ participants",
                    "infoEmpty": "Showing 0 to 0 of 0 participants",
                    "infoFiltered": "(filtered from _MAX_ total participants)",
                    "emptyTable": "No participants found",
                    "zeroRecords": "No matching participants found"
                },
                "drawCallback": function(settings){
                    let api = new $.fn.DataTable.Api(settings), pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                    pagination.toggle(api.page.info().pages > 1);
                    LazyImages.update();
                }
            });
        },
        addTypers : function(){
            $.each(opt.storage.who_typing, function() {
                if(!$("#typing_"+this[0]).length){
                    methods.updateBobbleHead(this[0], null)
                }
            });
            methods.drawBobbleHeads()
        },
        removeTypers : function(x){
            x = x || null;
            let time = new Date();
            if(x){
                opt.storage.who_typing.splice( $.inArray(x, opt.storage.who_typing), 1);
                methods.updateBobbleHead(x, opt.storage.messages[(opt.storage.messages.length-1)].message_id);
                methods.drawBobbleHeads();
                return;
            }
            if(opt.storage.who_typing.length){
                $.each(opt.storage.who_typing, function() {
                    if(((time.getTime() - this[2]) / 1000) > 2){
                        opt.storage.who_typing.splice( $.inArray(this[0], opt.storage.who_typing), 1);
                        methods.updateBobbleHead(this[0], opt.storage.messages[(opt.storage.messages.length-1)].message_id);
                    }
                });
                methods.drawBobbleHeads();
                return;
            }
            $('.typing-ellipsis').remove()
        },
        purgeMessage : function(id){
            let message = methods.locateStorageItem({type : 'message', id : id}), i = message.index;
            if (message.found){
                opt.storage.messages.splice(i, 1);
            }
            methods.imageLoadListener(false);
        },
        privateMainBobbleDraw : function(id){
            let bobble = methods.locateStorageItem({type : 'bobble', id : id}),
            status = $("#main_bobble_"+id);
            if(!status.length || !bobble.found) return;
            status.html(ThreadTemplates.render().thread_private_header_bobble(opt.storage.participants[bobble.index].owner));
            PageListeners.listen().tooltips();
            clearTimeout(opt.timers.private_bobble_refresh_timeout);
            if(opt.storage.participants[bobble.index].owner.options.online_status === 0){
                opt.timers.private_bobble_refresh_timeout = setTimeout(function(){
                    methods.privateMainBobbleDraw(id)
                }, 20000)
            }
        },
        drawBobbleHeads : function(){
            if(!opt.storage.participants.length || !opt.storage.messages.length) return;
            opt.storage.participants.forEach(function(value){
                if(value.owner_id === Messenger.common().id || !value.last_read.message_id || ('added' in value && value.added)) return;
                $(".bobble_head_"+value.owner_id).remove();
                let message = $("#message_"+value.last_read.message_id);
                if((value.caught_up && value.typing) || (opt.storage.messages[0].id === value.last_read.message_id)){
                    $("#seen-by_final").prepend(ThreadTemplates.render().bobble_head(value, true));
                    value.added = true;
                    value.caught_up = true
                }
                else if(message.length){
                    if(!message.next().hasClass('seen-by')) message.after(ThreadTemplates.render().seen_by(value.last_read.message_id));
                    $("#seen-by_"+value.last_read.message_id).prepend(ThreadTemplates.render().bobble_head(value, false));
                    value.added = true;
                    value.caught_up = false
                }
                if(opt.thread.type === 1){
                    methods.privateMainBobbleDraw(value.owner_id)
                }
            });
            $(".seen-by").each(function(){
                if(!$(this).children().length) $(this).remove()
            });
            methods.threadScrollBottom(false, false)
        },
        updateBobbleHead : function(owner, message){
            let typing = opt.storage.who_typing.filter( function(el) {
                return el.includes(owner);
            }),
            found = false;
            if(message === null){
                if(typing.length && opt.storage.messages.length){
                    message = opt.storage.messages[0].id
                }
                else{
                    message = false
                }
            }
            for(let x = 0; x < opt.storage.active_profiles.length; x++) {
                if (opt.storage.active_profiles[x].provider_id === owner){
                    found = true;
                    break;
                }
            }
            let bobble = methods.locateStorageItem({type : 'bobble', id : owner}), i = bobble.index;
            if (bobble.found){
                opt.storage.participants[i].last_read.message_id = (message ? message : opt.storage.participants[i].last_read.message_id);
                opt.storage.participants[i].added = false;
                opt.storage.participants[i].typing = !!typing.length;
                opt.storage.participants[i].caught_up = (typing.length ? true : opt.storage.participants[i].caught_up);
                opt.storage.participants[i].in_chat = (!!typing.length || found);
                $(".bobble_head_"+owner).remove();
                $(".seen-by").each(function(){
                    if(!$(this).children().length) $(this).remove()
                });
            }
        },
        checkRecentBobbleHeads : function(reload){
            if(reload){
                LoadIn.bobbleHeads();
                return;
            }
            for(let i = 0; i < opt.storage.participants.length; i++) {
                if (opt.storage.participants[i].caught_up && !opt.storage.participants[i].typing && opt.storage.messages[0].id !== opt.storage.participants[i].last_read.message_id){
                    methods.updateBobbleHead(opt.storage.participants[i].owner_id, opt.storage.participants[i].last_read.message_id)
                }
            }
            methods.drawBobbleHeads()
        },
        updateActiveProfile : function(owner, action){
            if(action === 3){
                for(let i = 0; i < opt.storage.active_profiles.length; i++) {
                    if (opt.storage.active_profiles[i].provider_id === owner){
                        opt.storage.active_profiles.splice(i, 1);
                        break;
                    }
                }
            }
            else {
                let bobble = methods.locateStorageItem({type : 'bobble', id : owner}), z = bobble.index;
                if(bobble.found){
                    opt.storage.participants[z].owner.options.online_status = action;
                }
            }

            methods.updateBobbleHead(owner, null);
            methods.drawBobbleHeads();
            if(action === 3 && opt.thread.type === 1) setTimeout(LoadIn.bobbleHeads, 6000);
        },
        imageLoadListener : function(scroll){
            let images = document.getElementsByClassName('msg_image'),
            emojis = document.getElementsByClassName('joypixels'),
            loadImage = function (e) {
                $(e.target).siblings('.spinner-grow').remove();
                $(e.target).removeClass('msg_image NS');
                if(scroll) methods.threadScrollBottom(true, false);
                if(e.type === 'error') e.target.src = [window.location.protocol, '//', window.location.host].join('')+'/vendor/messenger/image404.png';
            },
            loadEmoji = function (e) {
                if(scroll) methods.threadScrollBottom(true, false);
                if(e.type === 'error') $(e.target).remove()
            };
            [].forEach.call( images, function( img ) {
                img.addEventListener( 'load', loadImage, false );
                img.addEventListener( 'error', loadImage, false );
            });
            [].forEach.call( emojis, function( img ) {
                img.addEventListener( 'load', loadEmoji, false );
                img.addEventListener( 'error', loadEmoji, false );
            });
        },
        manageRecentMessages : function(){
            let messages_html = '';
            opt.storage.messages.reverse().forEach(function(value, key){
                if(value.system_message){
                    messages_html += ThreadTemplates.render().system_message(value);
                    return;
                }
                if(value.owner_id === Messenger.common().id){
                    if(key !== 0
                        && opt.storage.messages[key-1].owner_id === value.owner_id
                        && ! opt.storage.messages[key-1].system_message
                        && ! value.hasOwnProperty('reply_to')
                        && Messenger.format().timeDiffInUnit(value.created_at, opt.storage.messages[key-1].created_at, 'minutes') < 30
                    ){
                        messages_html += ThreadTemplates.render().my_message_grouped(value);
                        return;
                    }
                    if(value.hasOwnProperty('reply_to')){
                        messages_html += ThreadTemplates.render().my_message_reply(value);
                        return;
                    }
                    messages_html += ThreadTemplates.render().my_message(value);
                    return;
                }
                if(key !== 0
                    && opt.storage.messages[key-1].owner_id === value.owner_id
                    && ! opt.storage.messages[key-1].system_message
                    && ! value.hasOwnProperty('reply_to')
                    && Messenger.format().timeDiffInUnit(value.created_at, opt.storage.messages[key-1].created_at, 'minutes') < 30
                ){
                    messages_html += ThreadTemplates.render().message_grouped(value);
                    return;
                }
                if(value.hasOwnProperty('reply_to')){
                    messages_html += ThreadTemplates.render().message_reply(value);
                    return;
                }
                messages_html += ThreadTemplates.render().message(value)
            });
            opt.elements.msg_stack.append(messages_html);
            opt.storage.messages.reverse();
            methods.imageLoadListener(true);
            methods.drawBobbleHeads();
            methods.threadScrollBottom(true, false);
            if(!opt.thread.thread_history){
                opt.elements.msg_stack.prepend(ThreadTemplates.render().end_of_history(opt.thread.created_at));
            }
        },
        manageHistoryMessages : function(data){
            $("#loading_history_marker").remove();
            let messages = data.data.filter(function(value){
                return ! methods.locateStorageItem({type : 'message', id :value.id }).found;
            });
            let last_message = opt.storage.messages.length ? opt.storage.messages[opt.storage.messages.length-1] : null;
            let messages_html = '';
            messages.forEach((value) => {
                opt.storage.messages.push(value)
            });
            messages.reverse();
            messages.forEach(function(value, key){
                if(value.system_message){
                    messages_html += ThreadTemplates.render().system_message(value);
                    return;
                }
                if(value.owner_id === Messenger.common().id){
                    if(key !== 0
                        && messages[key-1].owner_id === value.owner_id
                        && ! messages[key-1].system_message
                        && ! value.hasOwnProperty('reply_to')
                        && Messenger.format().timeDiffInUnit(value.created_at, messages[key-1].created_at, 'minutes') < 30
                    ){
                        messages_html += ThreadTemplates.render().my_message_grouped(value);
                        return;
                    }
                    if(value.hasOwnProperty('reply_to')){
                        messages_html += ThreadTemplates.render().my_message_reply(value);
                        return;
                    }
                    messages_html += ThreadTemplates.render().my_message(value);
                    return;
                }
                if(key !== 0
                    && messages[key-1].owner_id === value.owner_id
                    && ! messages[key-1].system_message
                    && ! value.hasOwnProperty('reply_to')
                    && Messenger.format().timeDiffInUnit(value.created_at, messages[key-1].created_at, 'minutes') < 30
                ){
                    messages_html += ThreadTemplates.render().message_grouped(value);
                    return;
                }
                if(value.hasOwnProperty('reply_to')){
                    messages_html += ThreadTemplates.render().message_reply(value);
                    return;
                }
                messages_html += ThreadTemplates.render().message(value)
            });
            opt.elements.msg_stack.prepend(messages_html);
            if(messages.length
                && last_message !== null
                && ! last_message.system_message
                && ! messages[messages.length-1].system_message
                && ! last_message.hasOwnProperty('reply_to')
                && ! messages[messages.length-1].hasOwnProperty('reply_to')
                && messages[messages.length-1].owner_id === last_message.owner_id)
            {
                let replace_html = last_message.owner_id === Messenger.common().id
                    ? ThreadTemplates.render().my_message_grouped(last_message)
                    : ThreadTemplates.render().message_grouped(last_message);
                opt.elements.msg_stack.find("#message_"+last_message.id).replaceWith(replace_html)

            }
            if(opt.elements.the_thread.prop("scrollTop") === 0){
                if(opt.storage.messages.length && opt.storage.messages[opt.storage.messages.length-1].id !== data.meta.page_id){
                    document.getElementById('message_'+data.meta.page_id).scrollIntoView();
                    document.getElementById('msg_thread_'+opt.thread.id).scrollTop -= 40;
                    if(Messenger.common().mobile) window.scrollTo(0, 0)
                }
                else opt.elements.the_thread.scrollTop(40);
            }
            methods.imageLoadListener(false);
            methods.drawBobbleHeads();
            if(!messages.length || data.meta.final_page){
                opt.thread.thread_history = false;
                opt.thread.history_route = null;
                opt.thread.history_id = null;
                opt.elements.msg_stack.prepend(ThreadTemplates.render().end_of_history(opt.thread.created_at));
            }
            else{
                opt.thread.history_route = data.meta.next_page_route;
                opt.thread.history_id = data.meta.next_page_id;
            }
            opt.thread.history_loading = false;
            PageListeners.listen().tooltips()
        },
        initializeRecentMessages : function(reset) {
            let onLoad = function (data) {
                if(data){
                    opt.storage.messages = data.data;
                    if(data.meta.final_page) opt.thread.thread_history = false;
                }
                opt.elements.msg_stack.html('');
                methods.manageRecentMessages();
                if(opt.storage.pending_messages.length){
                    opt.storage.pending_messages.forEach(methods.addMessage);
                    opt.storage.pending_messages = [];
                    methods.markRead();
                }
                if(!opt.storage.participants.length) LoadIn.bobbleHeads()
            };
            if(!reset && opt.storage.messages.length){
                onLoad()
            }
            else{
                opt.states.lock = true;
                Messenger.xhr().request({
                    route : Messenger.common().API+'threads/'+opt.thread.id+'/messages',
                    success : onLoad,
                    fail : function(){
                        opt.states.load_in_retries++;
                        if(opt.states.load_in_retries > 4){
                            opt.elements.msg_stack.html('');
                            Messenger.alert().Alert({
                                toast : true,
                                theme : 'warning',
                                title : 'We could not load in your messages at this time'
                            });
                            return;
                        }
                        methods.initializeRecentMessages()
                    }
                });
            }
        },
        loadHistory : function(){
            if(opt.states.lock || opt.thread.history_loading || !opt.thread.thread_history || !opt.storage.messages.length) return;
            opt.states.lock = true;
            opt.thread.history_loading = true;
            opt.elements.msg_stack.prepend(ThreadTemplates.render().loading_history());
            Messenger.xhr().request({
                route : opt.thread.history_route,
                success : methods.manageHistoryMessages,
                fail : function(){
                    $("#loading_history_marker").remove();
                },
                bypass : true,
                fail_alert : true
            })
        },
        isTyping : function() {
            let time = new Date();
            if(opt.socket.online_status_setting === 1 && opt.storage.active_profiles.length && opt.socket.chat && ((time.getTime() - opt.socket.send_typing) / 1000) > 1.5){
                opt.socket.send_typing = time.getTime();
                opt.socket.chat.whisper('typing', {
                    provider_id: Messenger.common().id,
                    provider_alias : Messenger.common().model,
                    name: Messenger.common().name,
                    typing: true
                });
            }
        },
        stopTyping : function(){
            if(opt.socket.online_status_setting === 1 && opt.storage.active_profiles.length && opt.socket.chat && opt.socket.send_typing > 0){
                opt.socket.send_typing = 0;
                opt.socket.chat.whisper('typing', {
                    provider_id: Messenger.common().id,
                    provider_alias : Messenger.common().model,
                    name: Messenger.common().name,
                    typing: false
                });
            }
        },
        seenMessage : function(message){
            if(opt.storage.active_profiles.length && opt.socket.chat){
                opt.socket.chat.whisper('read', {
                    provider_id: Messenger.common().id,
                    provider_alias : Messenger.common().model,
                    message_id : message
                });
            }
        },
        sendOnlineStatus : function(status){
            if(!opt.storage.active_profiles.length || !opt.socket.chat) return;
            opt.socket.chat.whisper('online', {
                provider_id: Messenger.common().id,
                provider_alias : Messenger.common().model,
                name: Messenger.common().name,
                online_status: (opt.socket.online_status_setting !== 0 ? status : 0)
            })
        },
        pasteImage : function(event){
            if(opt.thread.type === 3) return;
            let items = (event.clipboardData  || event.originalEvent.clipboardData).items,
            blob = null;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf("image") === 0) {
                    blob = items[i].getAsFile();
                }
            }
            if (blob !== null) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    let file = event.target.result;
                    Messenger.alert().Modal({
                        size : 'lg',
                        theme : 'dark',
                        icon : 'image',
                        backdrop_ctrl : false,
                        title : 'Send Screenshot?',
                        body : '<img class="img-fluid" src="'+file+'"><canvas class="NS" id="paste_canvas"></canvas>',
                        cb_btn_txt : 'Send',
                        cb_btn_icon : 'cloud-upload-alt',
                        cb_btn_theme : 'success',
                        onReady : function(){
                            let canvas = document.getElementById("paste_canvas"),
                            ctx = canvas.getContext("2d"),
                            image = new Image();
                            image.onload = function() {
                                canvas.width = image.width;
                                canvas.height = image.height;
                                ctx.drawImage(image, 0, 0);
                            };
                            image.src = file
                        },
                        callback : function(){
                            document.getElementById("paste_canvas").toBlob(function(blob){
                                methods.sendUploadFiles(blob);
                                $(".modal").modal('hide');
                                opt.elements.message_text_input.focus()
                            }, 'image/png')
                        }
                    });
                };
                reader.readAsDataURL(blob)
            }
        },
        sendMessage : function(){
            if(!opt.thread.id || opt.thread.lockout || !opt.thread.messaging) return;
            let message_contents = opt.elements.message_text_input.val();
            if(message_contents.trim().length) {
                opt.elements.message_text_input.val('').focus();
                let pending = methods.makePendingMessage(0, message_contents);
                methods.managePendingMessage('add', pending);
                let formData = {
                    message : message_contents,
                    temporary_id : pending.id
                };
                if(opt.thread.replying){
                    formData.reply_to_id = opt.thread.reply_to_id;
                    methods.resetReplying();
                }
                Messenger.xhr().payload({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/messages',
                    data : formData,
                    success : function(x){
                        methods.managePendingMessage('completed', pending, x);
                    },
                    fail : function(){
                        methods.managePendingMessage('purge', pending);
                    },
                    fail_alert : true,
                    bypass : true
                });
                methods.manageSendMessageButton()
            }
        },
        sendUploadFiles : function(file, getType, audioMessage){
            let type = {
                number : 0,
                input : null,
                path : null
            },
            images = [
                'image/jpeg',
                'image/png',
                'image/bmp',
                'image/gif',
                'image/webp',
            ],
            audio = [
                'audio/aac',
                'audio/mpeg',
                'audio/ogg',
                'audio/wav',
                'audio/webm',
            ],
            files = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/x-rar-compressed',
                'application/x-rar',
                'application/zip',
                'application/x-7z-compressed',
                'application/x-zip-compressed',
                'application/zip-compressed',
                'multipart/x-zip',
                'text/plain',
                'text/xml',
                'application/rtf',
                'application/json',
                'text/csv',
            ];
            if(images.includes(file.type)){
                type.number = 1;
                type.input = 'image';
                type.path = '/images';
            } else if(files.includes(file.type)){
                type.number = 2;
                type.input = 'document';
                type.path = '/documents';
            } else if(audio.includes(file.type)){
                type.number = 3;
                type.input = 'audio';
                type.path = '/audio';
            }
            if(type.number === 0){
                Messenger.alert().Alert({
                    title : 'File type not supported',
                    toast : true,
                    theme : 'warning'
                });
                return;
            }
            if(getType === true){
                return type.input;
            }
            let pending = methods.makePendingMessage(type.number, null);
            methods.managePendingMessage('add', pending);
            let form = new FormData();
            if(audioMessage === true){
                form.append(type.input, file, 'audio_message.webm');
                form.append('extra', JSON.stringify({audio_message : true}));
            }else {
                form.append(type.input, file);
            }
            form.append('temporary_id', pending.id);
            if(opt.thread.replying){
                form.append('reply_to_id', opt.thread.reply_to_id);
                methods.resetReplying();
            }
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + type.path,
                data : form,
                success : function(x){
                    methods.managePendingMessage('completed', pending, x)
                },
                fail : function(){
                    methods.managePendingMessage('purge', pending)
                },
                fail_alert : true,
                bypass : true
            })
        },
        makePendingMessage : function(type, body){
            return {
                body : body ? Messenger.format().escapeHtml(body) : null,
                id : uuid.v4(),
                type : type,
                owner_id : Messenger.common().id,
                thread_id : opt.thread.id,
            }
        },
        addPendingMessage : function(message){
            if(opt.storage.pending_messages.length > 1 ||
                (opt.storage.messages.length > 1
                && opt.storage.messages[0].owner_id === Messenger.common().id
                && ! opt.storage.messages[0].system_message)
            ){
                opt.elements.pending_msg_stack.append(ThreadTemplates.render().pending_message_grouped(message));
            }
            else{
                opt.elements.pending_msg_stack.append(ThreadTemplates.render().pending_message(message))
            }
        },
        managePendingMessage : function(action, pending, final){
            let msg_elm = $("#pending_message_"+pending.id),
                storage = methods.locateStorageItem({type : 'pending_message', id : pending.id});
            switch (action) {
                case 'add':
                    opt.storage.pending_messages.push(pending);
                    methods.addPendingMessage(pending);
                    methods.messageStatusState(pending, false);
                    setTimeout(function () {
                        $("#pending_message_loading_"+pending.id).show()
                    }, 1500);
                break;
                case 'completed':
                    msg_elm.remove();
                    if(storage.found) opt.storage.pending_messages.splice(storage.index, 1);
                    methods.addMessage(final);
                break;
                case 'purge':
                    $("#pending_message_loading_"+pending.id).removeClass('text-primary').addClass('text-danger').show();
                    setTimeout(function () {
                        msg_elm.remove();
                    }, 5000);
                    if(storage.found) opt.storage.pending_messages.splice(storage.index, 1);
                break;
            }
        },
        locateStorageItem : function(arg){
            let collection, term,
            item = {
                found : false,
                index : 0
            };
            switch(arg.type){
                case 'message':
                    collection = opt.storage.messages;
                    term = 'id';
                break;
                case 'pending_message':
                    collection = opt.storage.pending_messages;
                    term = 'id';
                break;
                case 'thread':
                    collection = opt.storage.threads;
                    term = 'id';
                break;
                case 'bobble':
                    collection = opt.storage.participants;
                    term = 'owner_id';
                break;
            }
            for(let i = 0; i < collection.length; i++) {
                if (collection[i][term] === arg.id) {
                    item.found = true;
                    item.index = i;
                    break;
                }
            }
            return item
        },
        addMessage : function(msg){
            if(msg.thread_id !== opt.thread.id) return;
            if(methods.locateStorageItem({type : 'message', id : msg.id}).found) return;
            if(msg.temporary_id){
                let pending = methods.locateStorageItem({type : 'pending_message', id : msg.temporary_id});
                if(pending.found){
                    msg.temporary_id = null;
                    methods.managePendingMessage('completed', opt.storage.pending_messages[pending.index], msg);
                    return;
                }
            }
            methods.updateThread(msg, false, false, true);
            opt.storage.messages.unshift(msg);
            methods.updateBobbleHead(msg.owner_id, msg.id);
            if(msg.system_message){
                opt.elements.msg_stack.append(ThreadTemplates.render().system_message(msg));
            }
            else if(msg.hasOwnProperty('reply_to')){
                msg.owner_id === Messenger.common().id ? opt.elements.msg_stack.append(ThreadTemplates.render().my_message_reply(msg)) : opt.elements.msg_stack.append(ThreadTemplates.render().message_reply(msg));
            }
            else if(opt.storage.messages.length > 1
                && opt.storage.messages[1].owner_id === msg.owner_id
                && ! opt.storage.messages[1].system_message
                && Messenger.format().timeDiffInUnit(msg.created_at, opt.storage.messages[1].created_at, 'minutes') < 30
            ){
                msg.owner_id === Messenger.common().id ? opt.elements.msg_stack.append(ThreadTemplates.render().my_message_grouped(msg)) : opt.elements.msg_stack.append(ThreadTemplates.render().message_grouped(msg));
            }
            else{
                msg.owner_id === Messenger.common().id ? opt.elements.msg_stack.append(ThreadTemplates.render().my_message(msg)) : opt.elements.msg_stack.append(ThreadTemplates.render().message(msg));
            }
            methods.messageStatusState(msg, true);
            methods.drawBobbleHeads();
            if(opt.timers.recent_bobble_timeout) clearTimeout(opt.timers.recent_bobble_timeout);
            opt.timers.recent_bobble_timeout = setTimeout(function(){
                methods.checkRecentBobbleHeads([88,97,98,99].includes(msg.type))
            }, 3000);
        },
        messageStatusState : function(message, sound){
            opt.thread.click_to_read = false;
            let didScroll = methods.threadScrollBottom(message.owner_id === Messenger.common().id, false),
            hide = function () {
                opt.elements.new_msg_alert.hide();
                opt.thread.messages_unread = false;
                opt.elements.message_container.removeClass('msg-ctnr-unread');
            };
            methods.imageLoadListener(didScroll);
            if(didScroll && document.hasFocus() && (!opt.socket.is_away || (opt.socket.is_away && opt.socket.online_status_setting === 2))){
                hide();
                if(message.owner_id !== Messenger.common().id || ![0,1,2].includes(message.type)) methods.markRead()
            }
            else if(message.owner_id === Messenger.common().id){
                if(![0,1,2].includes(message.type)) methods.markRead();
                hide();
            }
            else{
                opt.thread.messages_unread = true;
                opt.elements.message_container.addClass('msg-ctnr-unread');
                if(!didScroll){
                    opt.elements.new_msg_alert.show();
                    opt.elements.new_msg_alert.html(ThreadTemplates.render().thread_new_message_alert())
                }
                else{
                    opt.thread.click_to_read = true;
                }
                if(sound) NotifyManager.sound('message')
            }
        },
        threadCallStatus : function(call, action){
            PageListeners.listen().disposeTooltips();
            //incoming joined ended left
            let thread = methods.locateStorageItem({type : 'thread', id : call.thread_id}), call_area = $("#thread_option_call");
            if(!thread.found){
                LoadIn.thread(call.thread_id);
                return;
            }
            if(!opt.storage.threads[thread.index].has_call && ['joined', 'left', 'incoming'].includes(action)){
                LoadIn.thread(call.thread_id, function(data){
                    if(opt.thread.id === call.thread_id){
                        call_area.html(ThreadTemplates.render().thread_call_state(data));
                    }
                    PageListeners.listen().tooltips()
                });
                return;
            }
            if(opt.storage.threads[thread.index].has_call){
                if(action === 'ended'){
                    delete opt.storage.threads[thread.index].resources.active_call;
                    opt.storage.threads[thread.index].has_call = false;
                }
                else if(action === 'joined'){
                    opt.storage.threads[thread.index].resources.active_call.options.in_call = true;
                    opt.storage.threads[thread.index].resources.active_call.options.joined = true;
                    opt.storage.threads[thread.index].resources.active_call.options.left_call = false;
                }
                else if(action === 'left'){
                    opt.storage.threads[thread.index].resources.active_call.options.in_call = false;
                    opt.storage.threads[thread.index].resources.active_call.options.joined = true;
                    opt.storage.threads[thread.index].resources.active_call.options.left_call = true;
                }
                let temp = opt.storage.threads[thread.index];
                opt.storage.threads.splice(thread.index, 1);
                opt.storage.threads.unshift(temp);
                methods.addThread(temp, true);
                if(opt.thread.id === call.thread_id){
                    if(action === 'ended'){
                        opt.thread.can_call ? call_area.html(ThreadTemplates.render().thread_call_state(temp)) : call_area.html('');
                    }
                    else{
                        call_area.html(ThreadTemplates.render().thread_call_state(temp));
                    }
                }
                PageListeners.listen().tooltips()
            }
            else{
                LoadIn.thread(call.thread_id, function(data){
                    if(opt.thread.id === call.thread_id){
                        opt.thread.can_call ? call_area.html(ThreadTemplates.render().thread_call_state(data)) : call_area.html('');
                    }
                    PageListeners.listen().tooltips()
                });
            }
        },
        threadOnlineStatus : function(state){
            if(opt.thread.type !== 1) return;
            let thread = methods.locateStorageItem({type : 'thread', id : opt.thread.id});
            if(thread.found){
                opt.storage.threads[thread.index].resources.recipient.options.online_status = state;
                methods.addThread(opt.storage.threads[thread.index], false);
            }
        },
        removeThread : function(thread_id){
            let the_thread = methods.locateStorageItem({type : 'thread', id : thread_id}), elm = $("#thread_list_"+thread_id);
            if(the_thread.found){
                opt.storage.threads.splice(the_thread.index, 1)
            }
            elm.remove();
            methods.calcUnreadThreads()
        },
        updateThread : function(data, thread, read, top){
            let the_thread = methods.locateStorageItem({type : 'thread', id : (thread ? data.id : data.thread_id)});
            if(!the_thread.found){
                if(thread){
                    opt.storage.threads.unshift(data);
                    methods.addThread(data, true);
                }
                else if("thread_id" in data){
                    LoadIn.thread(data.thread_id)
                }
                else{
                    LoadIn.threads();
                }
                return;
            }
            if(read){
                opt.storage.threads[the_thread.index].unread = false;
                opt.storage.threads[the_thread.index].unread_count = 0;
                methods.addThread(opt.storage.threads[the_thread.index], top);
                return;
            }
            if(thread){
                opt.storage.threads[the_thread.index] = data;
                methods.addThread(data, top);
                return;
            }
            let temp = opt.storage.threads[the_thread.index];
            opt.storage.threads.splice(the_thread.index, 1);
            temp.resources.latest_message = data;
            temp.updated_at = data.created_at;
            if(temp.type === 1 && data.thread_id !== opt.thread.id && data.owner_id !== Messenger.common().id) temp.resources.recipient.options.online_status = 1;
            if(temp.type === 1 && data.thread_id === opt.thread.id && data.owner_id !== Messenger.common().id){
                let bobble = methods.locateStorageItem({type : 'bobble', id : data.owner_id}), i = bobble.index;
                if(bobble.found){
                    temp.resources.recipient.options.online_status = opt.storage.participants[i].owner.options.online_status;
                }
            }
            if(data.owner_id === Messenger.common().id){
                temp.unread = false;
                temp.unread_count = 0;
            }
            else if(opt.thread.id !== data.thread_id || !document.hasFocus() || opt.socket.is_away || !methods.threadScrollBottom(false, true)){
                temp.unread = true;
                temp.unread_count = temp.unread_count+1;
            }
            else{
                temp.unread = false;
                temp.unread_count = 0;
            }
            opt.storage.threads.unshift(temp);
            methods.addThread(temp, top)
        },
        addThread : function(data, top){
            methods.calcUnreadThreads();
            if(!opt.elements.thread_area.length) return;
            if(opt.states.thread_filtered){
                methods.drawThreads();
                return;
            }
            methods.checkShowThreadSearch();
            $("#no_message_warning").remove();
            let thread_elm = opt.elements.thread_area.find('#thread_list_'+data.id),
            selected = data.id === opt.thread.id;
            if(selected){
                opt.elements.thread_area.find('.thread_list_item').removeClass('alert-warning shadow-sm rounded');
                opt.elements.thread_area.find('.thread-group-avatar').removeClass('avatar-is-online').addClass('avatar-is-offline')
            }
            if(top || !thread_elm.length){
                thread_elm.remove();
                opt.elements.thread_area.prepend((data.type === 2 ? ThreadTemplates.render().group_thread(data, selected) : ThreadTemplates.render().private_thread(data, selected)))
            }
            else{
                thread_elm.replaceWith((data.type === 2 ? ThreadTemplates.render().group_thread(data, selected) : ThreadTemplates.render().private_thread(data, selected)))
            }
        },
        drawThreads : function(){
            methods.checkShowThreadSearch();
            opt.elements.thread_area.html('');
            if(!opt.states.thread_filtered){
                opt.storage.threads.forEach(function(value){
                    opt.elements.thread_area.append((value.group ?
                        ThreadTemplates.render().group_thread(value, value.id === opt.thread.id)
                        : ThreadTemplates.render().private_thread(value, value.id === opt.thread.id))
                    )
                });
                return;
            }
            let filtered = opt.storage.threads.filter(function (thread) {
                return thread.name.toLowerCase().includes(opt.states.thread_filter_search.toLowerCase())
            });
            if(filtered.length){
                filtered.forEach(function(value){
                    opt.elements.thread_area.append((value.group ?
                        ThreadTemplates.render().group_thread(value, value.id === opt.thread.id)
                        : ThreadTemplates.render().private_thread(value, value.id === opt.thread.id))
                    )
                });
                return;
            }
            opt.elements.thread_area.html('<h4 id="no_message_warning" class="text-center mt-4"><span class="badge badge-pill badge-secondary"><i class="fas fa-comment-slash"></i> No matches</span></h4>');
        },
        checkThreadFilters : function(e){
            if(e && e.type === 'mouseup'){
                setTimeout(methods.checkThreadFilters, 0);
                return;
            }
            let filtered = opt.states.thread_filtered, search = opt.states.thread_filter_search;
            if(opt.elements.thread_search_input.val().trim().length){
                opt.states.thread_filtered = true;
                opt.states.thread_filter_search = opt.elements.thread_search_input.val();
                if(search !== opt.states.thread_filter_search) methods.drawThreads()
            }
            else{
                opt.states.thread_filtered = false;
                opt.states.thread_filter_search = null;
                if(filtered) methods.drawThreads()
            }
        },
        checkShowThreadSearch : function(){
            if(!opt.storage.threads.length){
                opt.elements.thread_search_bar.hide();
                return;
            }
            opt.elements.thread_search_bar.show()
        },
        calcUnreadThreads : function(){
            let unread = 0;
            opt.storage.threads.forEach(function(thread){
                if(thread.unread && thread.unread_count > 0) unread++;
            });
            NotifyManager.updateMessageCount({total_unread : unread})
        },
        editMessage : function(arg){
            if(!opt.thread.id) return;
            let messageStorage = methods.locateStorageItem({type : 'message', id : arg.id}), i = messageStorage.index, msg = $("#message_"+arg.id);
            if (messageStorage.found && opt.storage.messages[i].owner_id === Messenger.common().id){
                msg.find('.message-body').addClass('shadow-success');
                Messenger.alert().Modal({
                    icon : 'edit',
                    theme : 'dark',
                    title: 'Editing Message',
                    h4: false,
                    backdrop_ctrl : false,
                    unlock_buttons : false,
                    body : ThreadTemplates.render().edit_message(Messenger.format().shortcodeToUnicode(opt.storage.messages[i].body)),
                    cb_btn_txt : 'Update',
                    cb_btn_icon : 'edit',
                    cb_btn_theme : 'success',
                    onReady : function(){
                        setTimeout(function () {
                            Messenger.format().focusEnd(document.getElementById('edit_message_textarea'));
                            PageListeners.listen().tooltips();
                        }, 500)
                    },
                    callback : function(){
                        methods.updateMessage(arg)
                    },
                    onClosed : function(){
                        msg.find('.message-body').removeClass('shadow-success');
                    }
                });
            }
        },
        replyToMessage : function(arg){
            if(!opt.thread.id) return;
            if(opt.thread.replying){
                methods.resetReplying();
            }
            let messageStorage = methods.locateStorageItem({type : 'message', id : arg.id}),
                i = messageStorage.index,
                msg = $("#message_"+arg.id),
                focus_input = document.getElementById('message_text_input');
            if (messageStorage.found && ! opt.storage.messages[i].system_message){
                msg.find('.message-body').addClass('shadow-primary');
                opt.elements.reply_message_alert.show();
                opt.elements.reply_message_alert.html(ThreadTemplates.render().thread_replying_message_alert(opt.storage.messages[i]));
                opt.thread.replying = true;
                opt.thread.reply_to_id = arg.id;
                Messenger.format().focusEnd(focus_input);
            }
        },
        resetReplying : function(){
            if(!opt.thread.id || !opt.thread.replying) return;
            let msg = $("#message_"+opt.thread.reply_to_id);
            opt.elements.reply_message_alert.hide();
            opt.elements.reply_message_alert.html('');
            opt.thread.replying = false;
            opt.thread.reply_to_id = null;
            msg.find('.message-body').removeClass('shadow-primary');
        },
        updateMessage : function(arg){
            let textarea = $("#edit_message_textarea");
            textarea.prop('disabled', true);
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/messages/' + arg.id,
                data : {
                    message : textarea.val()
                },
                success : function(message){
                    methods.renderUpdatedMessage(message, true);
                },
                close_modal : true,
                fail_alert : true
            }, 'put');
        },
        renderUpdatedMessage : function(message, force){
            if(force === true && message.owner_id === Messenger.common().id){
                return;
            }
            let msg = $("#message_"+message.id), messageStorage = methods.locateStorageItem({type : 'message', id : message.id}), i = messageStorage.index;
            if (messageStorage.found){
                opt.storage.messages[i] = message;
            }
            if(msg.length){
                if(message.hasOwnProperty('reply_to')){
                    msg.replaceWith(message.owner_id === Messenger.common().id ? ThreadTemplates.render().my_message_reply(message) : ThreadTemplates.render().message_reply(message))
                }
                else{
                    msg.find('.message-text').html(ThreadTemplates.render().message_body(message))
                }
            }
        },
        addNewReaction : function(arg){
            if(!opt.thread.id) return;
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/messages/' + arg.message_id + '/reactions',
                data : {
                    reaction : arg.emoji
                },
                fail_alert : true
            });
        },
        removeReaction : function(arg, removeLi){
            if(!opt.thread.id) return;
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/messages/' + arg.message_id + '/reactions/'+arg.id,
                data : {},
                success : function(){
                    if(removeLi === true){
                        let reactLi = $("#react_li_item_"+arg.id);
                        if(reactLi.length){
                            reactLi.remove()
                        }
                    }
                },
                fail_alert : true
            }, 'delete');
        },
        updateNewReaction : function(reaction){
            let messageStorage = methods.locateStorageItem({type : 'message', id : reaction.message_id}),
                i = messageStorage.index;
            if (messageStorage.found){
                if(opt.storage.messages[i].hasOwnProperty('reactions')){
                    if(opt.storage.messages[i].reactions.data.hasOwnProperty(reaction.reaction)){
                        opt.storage.messages[i].reactions.data[reaction.reaction].push(reaction);
                    } else {
                        opt.storage.messages[i].reactions.data[reaction.reaction] = [reaction];
                        opt.storage.messages[i].reactions.meta.total_unique = Object.keys(opt.storage.messages[i].reactions.data).length;
                    }
                    opt.storage.messages[i].reactions.meta.total = opt.storage.messages[i].reactions.meta.total+1;
                } else {
                    opt.storage.messages[i].reacted = true;
                    opt.storage.messages[i].reactions = {
                        data : {},
                        meta : {
                            total : 1,
                            total_unique : 1
                        }
                    };
                    opt.storage.messages[i].reactions.data[reaction.reaction] = [reaction];
                }
                methods.drawReactions(opt.storage.messages[i])
            }
        },
        updateRemoveReaction : function(reaction){
            let messageStorage = methods.locateStorageItem({type : 'message', id : reaction.message_id}),
                i = messageStorage.index;
            if (messageStorage.found && opt.storage.messages[i].hasOwnProperty('reactions')){
                if(opt.storage.messages[i].reactions.data.hasOwnProperty(reaction.reaction)){
                    for(let y = 0; y < opt.storage.messages[i].reactions.data[reaction.reaction].length; y++) {
                        if (opt.storage.messages[i].reactions.data[reaction.reaction][y].id === reaction.id) {
                            opt.storage.messages[i].reactions.data[reaction.reaction].splice(y, 1);
                            break;
                        }
                    }
                    if(!opt.storage.messages[i].reactions.data[reaction.reaction].length){
                        delete opt.storage.messages[i].reactions.data[reaction.reaction];
                    }
                    let unique = Object.keys(opt.storage.messages[i].reactions.data).length;
                    if(!unique){
                        delete opt.storage.messages[i].reactions;
                        opt.storage.messages[i].reacted = false;
                    } else {
                        opt.storage.messages[i].reactions.meta.total_unique = unique;
                        opt.storage.messages[i].reactions.meta.total = opt.storage.messages[i].reactions.meta.total-1;
                    }
                }
                methods.drawReactions(opt.storage.messages[i])
            }
        },
        drawReactions : function(message){
            let msg = $("#message_"+message.id);
            if(msg.length){
                msg.find('.reactions').html(ThreadTemplates.render().message_reactions(message, msg.hasClass('my-message'), msg.hasClass('grouped-message')));
                methods.threadScrollBottom(false, false);
                PageListeners.listen().tooltips()
            }
        }
    },
    archive = {
        Message : function(arg){
            if(!opt.thread.id) return;
            let msg = $("#message_"+arg.id);
            msg.find('.message-body').addClass('shadow-warning');
            Messenger.alert().Modal({
                size : 'sm',
                body : false,
                centered : true,
                unlock_buttons : false,
                title: 'Delete message?',
                theme: 'danger',
                cb_btn_txt: 'Delete',
                cb_btn_theme : 'danger',
                cb_btn_icon:'trash',
                icon: 'trash',
                cb_close : true,
                callback : function(){
                    Messenger.xhr().payload({
                        route : Messenger.common().API + 'threads/' + opt.thread.id + '/messages/' + arg.id,
                        data : {},
                        success : function(){
                            Messenger.alert().Alert({
                                title : 'Message Removed',
                                toast : true,
                                theme : 'warning'
                            });
                            methods.purgeMessage(arg.id);
                            msg.remove()
                        },
                        fail_alert : true
                    }, 'delete');
                },
                onClosed : function(){
                    msg.find('.message-body').removeClass('shadow-warning');
                }
            });
        },
        Thread : function(){
            if(!opt.thread.id) return;
            Messenger.alert().Modal({
                theme : 'danger',
                icon : 'trash',
                backdrop_ctrl : false,
                pre_loader : true,
                title : 'Checking delete...',
                cb_btn_txt : 'Delete',
                cb_btn_icon : 'trash',
                cb_btn_theme : 'danger',
                onReady : function(){
                    Messenger.xhr().request({
                        route : Messenger.common().API + 'threads/' + opt.thread.id + '/check-archive',
                        success : function(data){
                            Messenger.alert().fillModal({body : ThreadTemplates.render().archive_thread_warning(data), title : ' Delete Conversation?'});
                        },
                        fail : Messenger.alert().destroyModal,
                        bypass : true,
                        fail_alert : true
                    })
                },
                callback : archive.postArchiveThread
            })
        },
        postArchiveThread : function(){
            if(opt.states.lock) return;
            opt.states.lock = true;
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id,
                shared : {
                    thread_id : opt.thread.id,
                    name : opt.thread.name,
                    type : opt.thread.type
                },
                data : {},
                success : function(data){
                    LoadIn.closeOpened(true);
                    let msg = "You removed the conversation between you and " + data.name;
                    if(data.type === 2){
                        msg = "You removed the group conversation " + data.name;
                    }
                    Messenger.alert().Alert({
                        title : msg,
                        theme : 'success',
                        toast : true
                    })
                },
                fail_alert : true,
                close_modal : true
            }, 'delete');

        }
    },
    groups = {
        viewParticipants : function(reload){
            let gather = () => {
                Messenger.xhr().request({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/participants',
                    success : function(data){
                        Messenger.alert().fillModal({
                            body : ThreadTemplates.render().group_participants(data.data, opt.thread.admin, opt.thread.lockout),
                            title : opt.thread.name+' Participants'
                        });
                        methods.loadDataTable($("#view_group_participants"))
                    },
                    fail_alert : true
                })
            };
            if(reload) return gather();
            Messenger.alert().Modal({
                icon : 'users',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Loading Participants...',
                pre_loader : true,
                overflow : true,
                unlock_buttons : false,
                h4 : false,
                size : 'lg',
                onReady : gather
            });
        },
        viewInviteGenerator : function(){
            Messenger.alert().Modal({
                backdrop_ctrl : false,
                icon : 'link',
                theme : 'dark',
                title : 'Loading Invite...',
                pre_loader : true,
                overflow : true,
                unlock_buttons : false,
                h4 : false,
                onReady : function(){
                    Messenger.xhr().request({
                        route : Messenger.common().API+'threads/'+opt.thread.id+'/invites',
                        success : groups.manageInviteGenPage,
                        fail_alert : true
                    })
                }
            });
        },
        manageInviteGenPage : function(data){
            let generate_click = function () {
                $("#grp_inv_generate_btn").click(groups.generateInviteLink)
            }, name = (CallManager.state().initialized ? CallManager.state().thread_name : opt.thread.name);
            if(data.data.length){
                Messenger.alert().fillModal({
                    body : ThreadTemplates.render().thread_show_invite(data.data),
                    title : name+' Invite Generator'
                });
                let btn_switch = $("#grp_inv_switch_generate_btn");
                btn_switch.click(function () {
                    Messenger.alert().fillModal({
                        body : ThreadTemplates.render().thread_generate_invite(true)
                    });
                    generate_click();
                    $("#grp_inv_back_btn").click(function () {
                        groups.manageInviteGenPage(data)
                    })
                });
            }
            else{
                Messenger.alert().fillModal({
                    body : ThreadTemplates.render().thread_generate_invite(false),
                    title : name+' Invite Generator'
                });
                generate_click()
            }
        },
        generateInviteLink : function(){
            let expire = parseInt($("#grp_inv_expires").val()), uses = parseInt($("#grp_inv_uses").val()),
                thread = (CallManager.state().initialized ? CallManager.state().thread_id : opt.thread.id),
                expires_at = null;
            switch (expire) {
                case 1:
                    expires_at = moment().utc().add(30, 'minutes').format('YYYY-MM-DD HH:mm:ss');
                break;
                case 2:
                    expires_at = moment().utc().add(1, 'hours').format('YYYY-MM-DD HH:mm:ss');
                break;
                case 3:
                    expires_at = moment().utc().add(6, 'hours').format('YYYY-MM-DD HH:mm:ss');
                break;
                case 4:
                    expires_at = moment().utc().add(12, 'hours').format('YYYY-MM-DD HH:mm:ss');
                break;
                case 5:
                    expires_at = moment().utc().add(1, 'days').format('YYYY-MM-DD HH:mm:ss');
                break;
                case 6:
                    expires_at = moment().utc().add(1, 'weeks').format('YYYY-MM-DD HH:mm:ss');
                break;
                case 7:
                    expires_at = moment().utc().add(2, 'weeks').format('YYYY-MM-DD HH:mm:ss');
                break;
                case 8:
                    expires_at = moment().utc().add(1, 'months').format('YYYY-MM-DD HH:mm:ss');
                break;
            }
            Messenger.alert().fillModal({
                loader : true,
                body : null,
                title : 'Generating...'
            });
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + thread + '/invites',
                data : {
                    expires : expires_at,
                    uses : uses
                },
                success : groups.viewInviteGenerator,
                fail : groups.viewInviteGenerator,
                bypass : true,
                fail_alert : true
            })
        },
        removeInviteLink : function(id){
            let thread = (CallManager.state().initialized ? CallManager.state().thread_id : opt.thread.id);
            Messenger.button().addLoader({id : '#inv_remove_btn_' + id});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + thread + '/invites/' + id,
                data : {},
                success : groups.viewInviteGenerator,
                fail : groups.viewInviteGenerator,
                bypass : true,
                fail_alert : true
            }, 'delete')
        },
        addParticipants : function(){
            let thread = (CallManager.state().initialized ? CallManager.state().thread_id : opt.thread.id),
                name = (CallManager.state().initialized ? CallManager.state().thread_name : opt.thread.name);
            Messenger.alert().Modal({
                icon : 'user-plus',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Loading friends...',
                pre_loader : true,
                cb_btn_txt : 'Add participants',
                cb_btn_icon : 'plus-square',
                cb_btn_theme : 'success',
                overflow : true,
                h4 : false,
                size : 'lg',
                unlock_buttons : false,
                onReady : function(){
                    Messenger.xhr().request({
                        route : Messenger.common().API + 'threads/'+thread+'/add-participants',
                        success : function(data){
                            Messenger.alert().fillModal({
                                body : ThreadTemplates.render().group_add_participants(data),
                                title : 'Add friends to '+name
                            });
                            methods.loadDataTable($("#add_group_participants"));
                        },
                        fail_alert : true
                    })
                },
                callback : function(){
                    let providers = [];
                    if(opt.elements.data_table){
                        opt.elements.data_table.$('input[type="checkbox"]:checked').map((key, value) => {
                            providers.push({alias : value.dataset.providerAlias, id : value.dataset.providerId})
                        })
                    }
                    Messenger.xhr().payload({
                        route : Messenger.common().API + 'threads/' + thread + '/participants',
                        data : {
                            providers : providers.length ? providers : null,
                        },
                        success : function(data){
                            if(data.length){
                                Messenger.alert().Alert({
                                    title : 'Participants added!',
                                    toast : true
                                })
                            }
                            else{
                                Messenger.alert().Alert({
                                    title : 'No valid participants found to add.',
                                    theme : 'error',
                                    toast : true
                                })
                            }
                        },
                        fail_alert : true,
                        close_modal : true
                    });
                }
            });
        },
        viewSettings : function(){
            if(opt.states.lock) return;
            opt.states.lock = true;
            Messenger.alert().Modal({
                icon : 'cog',
                theme : 'dark',
                title: 'Loading Settings...',
                pre_loader: true,
                h4: false,
                backdrop_ctrl : false,
                unlock_buttons : false,
                cb_btn_txt : 'Save Settings',
                cb_btn_icon : 'save',
                cb_btn_theme : 'success',
                onReady: function () {
                    Messenger.xhr().request({
                        route : Messenger.common().API + 'threads/' + opt.thread.id + '/settings',
                        success : function(data){
                            Messenger.alert().fillModal({
                                title : opt.thread.name+' Settings',
                                body : ThreadTemplates.render().group_settings(data)
                            });
                            PageListeners.listen().tooltips();
                            $(".m_setting_toggle").change(function(){
                                $(this).is(':checked') ? $(this).closest('tr').addClass('alert-success') : $(this).closest('tr').removeClass('alert-success')
                            })
                        },
                        fail_alert : true
                    })
                },
                callback : groups.saveSettings
            });
        },
        saveSettings : function(){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/settings',
                data : {
                    subject : $('#g_s_group_subject').val(),
                    add_participants : $("#g_s_add_participants").is(":checked"),
                    invitations : $("#g_s_invitations").is(":checked"),
                    calling : $("#g_s_admin_call").is(":checked"),
                    messaging : $("#g_s_send_message").is(":checked"),
                    knocks : $("#g_s_knocks").is(":checked"),
                    chat_bots : $("#g_s_bots").is(":checked"),
                },
                success : function(data){
                    Messenger.alert().Alert({
                        title : 'You updated '+data.name+'\'s Settings.',
                        toast : true
                    });
                },
                fail_alert : true,
                close_modal : true
            }, 'put');
        },
        groupAvatar : function(img){
            Messenger.alert().Modal({
                icon : 'image',
                theme : 'dark',
                centered : true,
                backdrop_ctrl : false,
                title: opt.thread.name+' Avatar',
                body : ThreadTemplates.render().group_avatar(img),
                h4: false,
                unlock_buttons : false,
                onReady: mounted.avatarListener
            });
        },
        updateGroupAvatar : function(arg){
            if(arg.action === 'upload'){
                let data = new FormData();
                data.append('image', $('#avatar_image_file')[0].files[0]);
                Messenger.button().addLoader({id : '#group_avatar_upload_btn'});
                Messenger.xhr().payload({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/avatar',
                    data : data,
                    success : function(data){
                        Messenger.alert().Alert({
                            title : 'You updated '+data.name+'\'s Avatar.',
                            toast : true
                        });
                    },
                    fail_alert : true,
                    close_modal : true
                });
                return;
            }
            Messenger.button().addLoader({id : '#avatar_default_btn'});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/avatar',
                data : {
                    default : $('#default_avatar input[type="radio"]:checked').val()
                },
                success : function(data){
                    Messenger.alert().Alert({
                        title : 'You updated '+data.name+'\'s Avatar.',
                        toast : true
                    });
                },
                fail_alert : true,
                close_modal : true
            });
        },
        removeParticipant : function(x){
            if(opt.states.lock) return;
            opt.states.lock = true;
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/participants/' + x,
                data : {},
                success : function(data){
                    Messenger.alert().Alert({
                        title : "Participant removed",
                        toast : true,
                        theme : 'success'
                    });
                    opt.elements.data_table.row($('#row_'+x)).remove().draw(false);
                },
                fail_alert : true
            }, 'delete');
        },
        promoteAdmin : function(participant){
            if(opt.states.lock) return;
            opt.states.lock = true;
            Messenger.alert().fillModal({loader : true});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/participants/' + participant + '/promote',
                data : {},
                success : function (data) {
                    groups.viewParticipants(true);
                },
                fail_alert : true
            });
        },
        demoteAdmin : function(participant){
            if(opt.states.lock) return;
            opt.states.lock = true;
            Messenger.alert().fillModal({loader : true});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/participants/' + participant + '/demote',
                data : {},
                success : function (data) {
                    groups.viewParticipants(true);
                },
                fail_alert : true
            });
        },
        participantPermissionsView : function(participant_id){
            if(opt.states.lock) return;
            opt.states.lock = true;
            Messenger.alert().Modal({
                icon : 'user-cog',
                theme : 'dark',
                title: 'Loading Permissions...',
                pre_loader: true,
                h4: false,
                backdrop_ctrl : false,
                unlock_buttons : false,
                cb_btn_txt : 'Save Permissions',
                cb_btn_icon : 'save',
                cb_btn_theme : 'success',
                onReady: function () {
                    Messenger.xhr().request({
                        route : Messenger.common().API + 'threads/' + opt.thread.id + '/participants/' + participant_id,
                        success : function(participant){
                            Messenger.alert().fillModal({
                                title : participant.owner.name+' Permissions',
                                body : ThreadTemplates.render().participant_permissions(participant)
                            });
                            PageListeners.listen().tooltips();
                            $(".m_setting_toggle").change(function(){
                                $(this).is(':checked') ? $(this).closest('tr').addClass('bg-light') : $(this).closest('tr').removeClass('bg-light')
                            })
                        },
                        fail_alert : true
                    })
                },
                callback : function(){
                    groups.participantPermissionSave(participant_id)
                }
            });
        },
        participantPermissionSave : function(participant_id){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/participants/' + participant_id,
                data : {
                    add_participants : $("#p_add_participants").is(":checked"),
                    manage_invites : $("#p_manage_invites").is(":checked"),
                    send_messages : $("#p_send_messages").is(":checked"),
                    send_knocks : $("#p_send_knocks").is(":checked"),
                    start_calls : $("#p_start_calls").is(":checked"),
                    manage_bots : $("#p_manage_bots").is(":checked"),
                },
                success : function(participant){
                    Messenger.alert().Alert({
                        title : 'You updated '+participant.owner.name+'\'s permissions.',
                        toast : true
                    });
                },
                fail_alert : true,
                close_modal : true
            }, 'put');
        },
        leaveGroup : function(){
            Messenger.alert().Modal({
                icon : 'sign-out-alt',
                backdrop_ctrl : false,
                centered : true,
                size : 'sm',
                h4 : false,
                theme : 'danger',
                title : 'Leave Group?',
                body : '<span class="h5 font-weight-bold">Are you sure you want to leave '+opt.thread.name+'?</span>',
                cb_btn_txt : 'Leave',
                cb_btn_icon : 'sign-out-alt',
                cb_btn_theme : 'danger',
                callback : function(){
                    Messenger.xhr().payload({
                        route : Messenger.common().API + 'threads/' + opt.thread.id + '/leave',
                        shared : {
                            thread_id : opt.thread.id,
                            name : opt.thread.name
                        },
                        data : {},
                        success : function(data){
                            LoadIn.closeOpened();
                            methods.removeThread(data.thread_id);
                            Messenger.alert().Alert({
                                title : "You left "+data.name,
                                toast : true,
                                theme : 'success'
                            })
                        },
                        fail_alert : true,
                        close_modal : true
                    })
                }
            })
        }
    },
    new_forms = {
        newGroup : function(){
            let subject = $("#subject").val(), providers = [];
            if(opt.states.lock || !subject.trim().length) return;
            opt.states.lock = true;
            opt.elements.message_container.html(ThreadTemplates.render().loading_thread_base());
            if(opt.elements.data_table){
                opt.elements.data_table.$('input[type="checkbox"]:checked').map((key, value) => {
                    providers.push({alias : value.dataset.providerAlias, id : value.dataset.providerId})
                })
            }
            Messenger.xhr().payload({
                route : Messenger.common().API + 'groups',
                data : {
                    providers : providers.length ? providers : null,
                    subject :  subject
                },
                success : function(x){
                    mounted.reset(true);
                    methods.initiateGroup({new : true}, x, false);
                },
                fail : LoadIn.closeOpened,
                fail_alert : true,
                bypass : true
            })
        },
        newPrivate : function(isFile, voiceMessage, audio){
            if(opt.states.lock) return;
            let form = new FormData(),
                message_contents = opt.elements.message_text_input.val();
            if(isFile === true){
                let file = $('#doc_file')[0].files[0];
                let type = methods.sendUploadFiles(file, true);
                form.append(type, file);
            } else if(voiceMessage === true) {
                form.append('audio', audio, 'audio_message.webm');
                form.append('extra', JSON.stringify({audio_message : true}));
            } else {
                if(!message_contents.trim().length) return;
                form.append('message', message_contents);
                opt.elements.message_text_input.val('').focus();
            }
            form.append('recipient_id', opt.storage.temp_data.provider_id);
            form.append('recipient_alias', opt.storage.temp_data.provider_alias);
            opt.states.lock = true;
            opt.elements.message_container.html(ThreadTemplates.render().loading_thread_base());
            Messenger.xhr().payload({
                route : Messenger.common().API + 'privates',
                data : form,
                success : function(x){
                    mounted.reset(true);
                    methods.initiatePrivate({new : true}, x, false);
                },
                fail : LoadIn.closeOpened,
                fail_alert : true,
                bypass : true
            })
        },
        threadApproval : function(approve){
            if(opt.states.lock || !opt.thread.id) return;
            opt.states.lock = true;
            Messenger.button().addLoader({id : approve ? '#thread_approval_accept_btn' : '#thread_approval_deny_btn'});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/approval',
                data : {
                    approve : approve
                },
                success : function(x){
                    Messenger.alert().Alert({
                        title : "You " + (approve ? 'approved' : 'denied') + ' the message request from ' + opt.thread.name,
                        toast : true,
                        theme : approve ? 'success' : 'error',
                    })
                    if(approve){
                        LoadIn.initiate_thread({thread_id : opt.thread.id, force : true})
                    }
                    else{
                        methods.removeThread(opt.thread.id);
                        LoadIn.closeOpened();
                    }
                },
                fail_alert : true,
                bypass : true
            })
        }
    },
    Calls = {
        showCreateModal : function(){
            Messenger.alert().Modal({
                size : 'sm',
                icon : 'user-plus',
                pre_loader : true,
                centered : true,
                unlock_buttons : false,
                allow_close : false,
                backdrop_ctrl : false,
                title: 'Creating Call',
                theme: 'success'
            });
        },
        initCall : function(){
            if(opt.states.lock) return;
            opt.states.lock = true;
            Messenger.button().addLoader({id : '.video_btn'});
            Calls.showCreateModal(false);
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/calls',
                data : {},
                success : function(data){
                    CallManager.join(data);
                    NotifyManager.heartbeat();
                    Messenger.button().removeLoader()
                },
                close_modal : true,
                fail_alert : true
            })
        },
        sendKnock : function(){
            if(opt.states.lock || !NotifyManager.sockets().status) return;
            Messenger.button().addLoader({id : '#knok_btn'});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/knock-knock',
                data : {},
                success : function(data){
                    NotifyManager.sound('knok');
                    Messenger.alert().Alert({
                        close : true,
                        title : 'Knock Knock!',
                        body : "You sent a knock to " + opt.thread.name + "!",
                        toast : true
                    })
                },
                fail_alert : true
            })
        }
    },
    Mute = {
        mute : function(){
            let payload = function(){
                Messenger.xhr().payload({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/mute',
                    data : {},
                    success : function(){
                        Messenger.alert().Alert({
                            close : true,
                            title : "You muted " + opt.thread.name,
                            toast : true
                        });
                        LoadIn.initiate_thread({thread_id : opt.thread.id, force : true, read : false})
                    },
                    fail_alert : true,
                    close_modal : true
                })
            };
            Messenger.alert().Modal({
                icon : 'volume-mute',
                size : 'md',
                backdrop_ctrl : false,
                h4 : false,
                theme : 'primary',
                title : 'Mute?',
                body : '<span class="h5 font-weight-bold">Really mute '+opt.thread.name+'? You will no longer receive any alerts or notifications from that conversation.</span>',
                cb_btn_txt : 'Mute',
                cb_btn_icon : 'volume-mute',
                cb_btn_theme : 'primary',
                callback : payload
            })

        },
        unmute : function(){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/unmute',
                data : {},
                success : function(){
                    Messenger.alert().Alert({
                        close : true,
                        title : "You un-muted " + opt.thread.name,
                        toast : true
                    });
                    LoadIn.initiate_thread({thread_id : opt.thread.id, force : true, read : false})
                },
                fail_alert : true
            })
        }
    },
    LoadIn = {
        closeOpened : function(force){
            if(opt.states.lock && !force) return;
            if(Messenger.common().mobile) ThreadTemplates.mobile(false);
            mounted.reset(false);
            mounted.Initialize({
                type : 5
            });
            window.history.pushState({type : 5}, null, Messenger.common().WEB)
        },
        messageEdits : function(route){
            if(!opt.thread.id) return;
            Messenger.alert().Modal({
                size : 'md',
                backdrop_ctrl : false,
                overflow : true,
                theme : 'dark',
                icon : 'edit',
                title: 'Loading Edit History...',
                pre_loader: true,
                h4: false,
                onReady: function () {
                    Messenger.xhr().request({
                        route : route,
                        success : function(data){
                            Messenger.alert().fillModal({
                                title : 'Message Edit History',
                                body : ThreadTemplates.render().message_edit_history(data)
                            });
                        }
                    })
                }
            })
        },
        threads : function(){
            Messenger.xhr().request({
                route : Messenger.common().API + 'threads',
                success : function(data){
                    opt.storage.threads = data.data;
                    if(opt.elements.thread_area.length){
                        if(!opt.storage.threads.length){
                            methods.checkShowThreadSearch();
                            opt.elements.thread_area.html('<h4 id="no_message_warning" class="text-center mt-4"><span class="badge badge-pill badge-secondary"><i class="fas fa-comments"></i> No conversations</span></h4>');
                            return;
                        }
                        methods.drawThreads();
                    }
                    methods.calcUnreadThreads()
                },
                fail : function(){
                    if(opt.states.load_in_retries >= 6){
                        Messenger.alert().Alert({
                            theme : 'error',
                            title : 'We could not load in your threads. Please try refreshing your browser page',
                            toast : true
                        });
                        return;
                    }
                    opt.states.load_in_retries++;
                    LoadIn.threads()
                }
            })
        },
        threadLogs : function(paginate, page){
            if(!opt.thread.id) return;
            if(paginate){
                $("#log_paginate_btn").html(Messenger.alert().loader(true));
                Messenger.xhr().request({
                    route : Messenger.common().API+'threads/'+opt.thread.id+'/logs/page/' + page,
                    success : function(data){
                        $("#log_paginate_btn").remove();
                        $("#body_modal").append(ThreadTemplates.render().thread_logs(data))
                    }
                })
                return;
            }
            Messenger.alert().Modal({
                size : 'lg',
                backdrop_ctrl : false,
                overflow : true,
                theme : 'dark',
                icon : 'database',
                title: 'Loading Logs...',
                pre_loader: true,
                h4: false,
                onReady: function () {
                    Messenger.xhr().request({
                        route : Messenger.common().API+'threads/'+opt.thread.id+'/logs',
                        success : function(data){
                            Messenger.alert().fillModal({
                                title : opt.thread.name+' Logs',
                                body : data.data.length ? ThreadTemplates.render().thread_logs(data) : '<h3 class="text-center mt-2"><span class="badge badge-pill badge-secondary"><i class="fas fa-database"></i> No logs</span></h3>'
                            });
                        }
                    })
                }
            })
        },
        threadImages : function(paginate, page){
            if(!opt.thread.id) return;
            if(paginate){
                $("#image_paginate_btn").html(Messenger.alert().loader(true));
                Messenger.xhr().request({
                    route : Messenger.common().API+'threads/'+opt.thread.id+'/images/page/' + page,
                    success : function(data){
                        $("#image_paginate_btn").remove();
                        $("#body_modal").append(ThreadTemplates.render().thread_images(data))
                        LazyImages.update();
                    }
                })
                return;
            }
            Messenger.alert().Modal({
                size : 'fullscreen',
                backdrop_ctrl : false,
                theme : 'dark',
                icon : 'images',
                title: 'Loading Images...',
                pre_loader: true,
                h4: false,
                onReady: function () {
                    Messenger.xhr().request({
                        route : Messenger.common().API+'threads/'+opt.thread.id+'/images',
                        success : function(data){
                            Messenger.alert().fillModal({
                                title : opt.thread.name+' Shared Images',
                                body : data.data.length ? ThreadTemplates.render().thread_images(data) : '<h3 class="text-center mt-2"><span class="badge badge-pill badge-secondary"><i class="fas fa-images"></i> No Images</span></h3>'
                            });
                            LazyImages.update();
                        }
                    })
                }
            })
        },
        threadDocuments : function(paginate, page){
            if(!opt.thread.id) return;
            if(paginate){
                $("#document_paginate_btn").html(Messenger.alert().loader(true));
                Messenger.xhr().request({
                    route : Messenger.common().API+'threads/'+opt.thread.id+'/documents/page/' + page,
                    success : function(data){
                        $("#document_paginate_btn").remove();
                        $("#documents_history").append(ThreadTemplates.render().thread_documents(false, data))
                    }
                })
                return;
            }
            Messenger.alert().Modal({
                size : 'lg',
                backdrop_ctrl : false,
                overflow : true,
                theme : 'dark',
                icon : 'file-alt',
                title: 'Loading Documents...',
                pre_loader: true,
                h4: false,
                onReady: function () {
                    Messenger.xhr().request({
                        route : Messenger.common().API+'threads/'+opt.thread.id+'/documents',
                        success : function(data){
                            Messenger.alert().fillModal({
                                title : opt.thread.name+' Shared Documents',
                                body : data.data.length ? ThreadTemplates.render().thread_documents(true, data) : '<h3 class="text-center mt-2"><span class="badge badge-pill badge-secondary"><i class="fas fa-file-alt"></i> No Documents</span></h3>'
                            });
                        }
                    })
                }
            })
        },
        messageReactions : function(messageId){
            if(!opt.thread.id) return;
            Messenger.alert().Modal({
                size : 'md',
                backdrop_ctrl : false,
                overflow : true,
                theme : 'dark',
                icon : 'grin-tongue',
                title: 'Loading Reactions...',
                pre_loader: true,
                h4: false,
                onReady: function () {
                    Messenger.xhr().request({
                        route : Messenger.common().API+'threads/'+opt.thread.id+'/messages/'+messageId+'/reactions',
                        success : function(data){
                            Messenger.alert().fillModal({
                                title : 'Message Reactions',
                                body : ThreadTemplates.render().show_message_reactions(data)
                            });
                        }
                    })
                }
            })
        },
        threadAudio : function(paginate, page){
            if(!opt.thread.id) return;
            if(paginate){
                $("#audio_paginate_btn").html(Messenger.alert().loader(true));
                Messenger.xhr().request({
                    route : Messenger.common().API+'threads/'+opt.thread.id+'/audio/page/' + page,
                    success : function(data){
                        $("#audio_paginate_btn").remove();
                        $("#audio_history").append(ThreadTemplates.render().thread_audio(false, data))
                    }
                })
                return;
            }
            Messenger.alert().Modal({
                size : 'lg',
                backdrop_ctrl : false,
                overflow : true,
                theme : 'dark',
                icon : 'music',
                title: 'Loading Audio...',
                pre_loader: true,
                h4: false,
                onReady: function () {
                    Messenger.xhr().request({
                        route : Messenger.common().API+'threads/'+opt.thread.id+'/audio',
                        success : function(data){
                            Messenger.alert().fillModal({
                                title : opt.thread.name+' Shared Audio',
                                body : data.data.length ? ThreadTemplates.render().thread_audio(true, data) : '<h3 class="text-center mt-2"><span class="badge badge-pill badge-secondary"><i class="fas fa-music"></i> No Audio</span></h3>'
                            });
                        }
                    })
                }
            })
        },
        thread : function(thread_id, success){
            Messenger.xhr().request({
                route : Messenger.common().API+'threads/' + thread_id,
                success : function(data){
                    let thread = methods.locateStorageItem({type : 'thread', id : thread_id});
                    if(!thread.found){
                        opt.storage.threads.unshift(data);
                    }
                    else{
                        opt.storage.threads.splice(thread.index, 1);
                        opt.storage.threads.unshift(data);
                    }
                    methods.addThread(data, true);
                    if(success) success(data)
                },
                fail_alert : true
            })
        },
        bobbleHeads : function(){
            if(!opt.thread.id) return;
            Messenger.xhr().request({
                route : Messenger.common().API+'threads/'+opt.thread.id+'/participants',
                success : function(data){
                    opt.storage.participants = data.data;
                    $(".bobble-head-item").remove();
                    if(opt.storage.active_profiles.length){
                        opt.storage.active_profiles.forEach(function(value){
                            methods.updateBobbleHead(value.provider_id, null)
                        })
                    }
                    if(opt.thread.type === 1 && opt.storage.participants.length){
                        for(let i = 0; i < opt.storage.participants.length; i++) {
                            if (opt.storage.participants[i].owner_id !== Messenger.common().id) {
                                methods.threadOnlineStatus(opt.storage.participants[i].owner.options.online_status);
                            }
                        }
                    }
                    methods.drawBobbleHeads()
                },
                fail : null
            })
        },
        search : function(noHistory){
            if(!opt.INIT) return;
            if(Messenger.common().mobile) ThreadTemplates.mobile(true);
            opt.elements.message_container.html(ThreadTemplates.render().search_base());
            mounted.reset(false);
            mounted.Initialize({
                type : 7,
            });
            if(!noHistory) window.history.pushState({type : 7}, null, Messenger.common().WEB + '?search');
        },
        contacts : function(noHistory){
            if(!opt.INIT) return;
            if(Messenger.common().mobile) ThreadTemplates.mobile(true);
            opt.elements.message_container.html(ThreadTemplates.render().contacts_base());
            mounted.reset(false);
            opt.thread.type = 6;
            Messenger.xhr().request({
                route : Messenger.common().API + 'friends',
                success : function(data){
                    $("#messenger_contacts_ctnr").html(ThreadTemplates.render().contacts(data));
                    if(!noHistory) window.history.pushState({type : 6}, null, Messenger.common().WEB + '?contacts');
                    methods.loadDataTable( $("#contact_list_table"), true)
                },
                fail : LoadIn.closeOpened,
                fail_alert : true,
                bypass : true
            })
        },
        createPrivate : function(arg, noHistory){
            if(CallManager.state().initialized){
                window.open(Messenger.common().WEB + '/recipient/'+arg.alias+'/'+arg.id);
                return;
            }
            opt.elements.message_container.html(ThreadTemplates.render().loading_thread_base());
            mounted.reset(false);
            if(Messenger.common().mobile) ThreadTemplates.mobile(true);
            $(".modal").modal('hide');
            Messenger.xhr().request({
                route : Messenger.common().API + 'privates/recipient/'+arg.alias+'/'+arg.id,
                success : function(data){
                    if(data.thread_id){
                        LoadIn.initiate_thread({thread_id : data.thread_id});
                        return;
                    }
                    opt.elements.message_container.html(ThreadTemplates.render().render_new_private(data.recipient));
                    if(!noHistory) window.history.pushState({type : 3, id : arg.id, alias : arg.alias}, null, Messenger.common().WEB + '/recipient/'+arg.alias+'/'+arg.id);
                    opt.thread.messaging = data.recipient.options.can_message_first;
                    mounted.Initialize({
                        type : 3,
                        thread_id : 'new',
                        t_name : data.recipient.name,
                        temp_data : data.recipient
                    })
                },
                fail : LoadIn.closeOpened,
                fail_alert : true,
                bypass : true
            })
        },
        createGroup : function(noHistory){
            if(opt.states.lock) return;
            mounted.reset(false);
            opt.elements.message_container.html(ThreadTemplates.render().new_group_base());
            if(!noHistory) window.history.pushState({type : 4}, null, Messenger.common().WEB + '?newGroup');
            if(Messenger.common().mobile) ThreadTemplates.mobile(true);
            mounted.Initialize({
                type : 4
            });
            Messenger.xhr().request({
                route : Messenger.common().API + 'friends',
                success : function(data){
                    if(opt.thread.type === 4){
                        $("#messages_container_new_group").html(ThreadTemplates.render().new_group_friends(data));
                        methods.loadDataTable($("#add_group_participants"), true)
                    }
                },
                fail_alert : true
            })
        },
        initiate_thread : function(arg, noHistory){
            if(opt.states.lock || (arg.thread_id === opt.thread.id && !("force" in arg))) return;
            if(Messenger.common().mobile) ThreadTemplates.mobile(true);
            opt.elements.message_container.html(ThreadTemplates.render().loading_thread_base());
            mounted.reset(true);
            opt.thread.initializing = true;
            opt.thread._id = arg.thread_id;
            let params = '/load/messages|participants';
            if( ! arg.hasOwnProperty('read')){
                params += '|mark-read';
            }
            Messenger.xhr().request({
                route : Messenger.common().API + 'threads/' + arg.thread_id + params,
                success : function(data){
                    data.group
                        ? methods.initiateGroup(arg, data, noHistory)
                        : methods.initiatePrivate(arg, data, noHistory);
                },
                fail : LoadIn.closeOpened,
                bypass : true,
                fail_alert : true
            })
        }
    };
    return {
        init : mounted.Initialize,
        Import : function(){
            return Imports
        },
        newForms : function(){
            return new_forms
        },
        calls : function(){
            return Calls
        },
        send : methods.sendMessage,
        archive : function(){
            return archive
        },
        editMessage : methods.editMessage,
        reply : methods.replyToMessage,
        addNewReaction : methods.addNewReaction,
        removeReaction : methods.removeReaction,
        mute : function(){
            return Mute;
        },
        group : function() {
            return groups
        },
        load : function(){
            return LoadIn
        },
        switchToggle : mounted.switchToggleListener,
        lock : function(arg){
            if(typeof arg === 'boolean') opt.states.lock = arg
        },
        state : function(){
            return {
                thread_id : opt.thread.id,
                thread_lockout : opt.thread.lockout,
                type : opt.thread.type,
                thread_admin : opt.thread.admin,
                t_name : opt.thread.name,
                _thread : opt.thread._thread,
                online_status : opt.socket.online_status_setting,
                socketStatusCheck : Health.checkConnection,
                reConnected : Health.reConnected,
                online : function(state){
                    methods.statusOnline(state, true)
                },
                statusSetting : methods.updateOnlineStatusSetting
            };
        }
    };
}());