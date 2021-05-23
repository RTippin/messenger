/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');
window.io = require('socket.io-client');

window.NotifyManager = (function () {
    var opt = {
        sounds : {
            notify_sound_file : new Audio([window.location.protocol, '//', window.location.host].join('')+'/vendor/messenger/sounds/notify_tone.mp3'),
            message_sound_file : new Audio([window.location.protocol, '//', window.location.host].join('')+'/vendor/messenger/sounds/message_tone.mp3'),
            call_sound_file : new Audio([window.location.protocol, '//', window.location.host].join('')+'/vendor/messenger/sounds/call_tone.mp3'),
            knok_sound_file : new Audio([window.location.protocol, '//', window.location.host].join('')+'/vendor/messenger/sounds/knok.mp3')
        },
        elements : {
            notify_count_area : $("#nav_notify_count"),
            thread_count_area : $("#nav_thread_count"),
            calls_count_area : $("#nav_calls_count"),
            active_call_nav_icon : $("#active_call_nav_icon"),
            pending_friends_count_area : $("#nav_friends_count"),
            mobile_nav_count_area : $("#nav_mobile_total_count"),
            notify_area : $("#notification_container"),
            active_calls_link : $("#active_calls_link"),
            pending_friends_link : $("#pending_friends_nav"),
            active_calls_ctnr : $("#active_calls_ctnr"),
            pending_friends_ctnr : $("#pending_friends_ctnr"),
            sent_friends_ctrn : $("#sent_friends_ctnr"),
            click_notify_bell : $("#click_notify_bell"),
            del_all_notify_link : $("#del_all_notify_link"),
            click_friends_tab : $("#click_friends_tab")
        },
        settings : {
            notifications : true,
            total_notify_count : 0,
            message_popups : true,
            notify_sound : true,
            message_sound : true,
            call_ringtone_sound : true,
            sound_playing : false,
            is_away : false,
            global_away : false,
            away : function () {
                return this.is_away && this.global_away;
            }
        },
        storage : {
            unread_notify : 0,
            unread_thread : 0,
            pending_friends_count : 0,
            active_calls_count : 0,
            active_calls : [],
            pending_friends : [],
            sent_friends : [],
            original_title : null,
            current_title : null,
            heartbeat_interval : null,
            heartbeat_interval_runs : 0,
            toggle_title_interval : null,
            last_call_update : null
        },
        socket : {
            Echo : null,
            private_channel : null,
            socket_status : false,
            forced_disconnect : false,
            private_channel_retries : 0,
            presence_channel_retries : 0,
        }
    },
    Initialize = {
        Init : function(arg){
            opt.settings.message_popups = arg.message_popups;
            opt.settings.notify_sound = arg.notify_sound;
            opt.settings.message_sound = arg.message_sound;
            opt.settings.call_ringtone_sound = arg.call_ringtone_sound;
            FriendsManager.init();
            if(!Messenger.common().websockets) opt.socket.forced_disconnect = true;
            broadcaster.Echo(false);
            broadcaster.heartBeat(true, false, false);
            broadcaster.heartBeat(false, true, true);
            opt.storage.original_title = document.head.querySelector('meta[name="title"]').content;
            opt.storage.current_title = opt.storage.original_title;
            Initialize.setListeners();
            InactivityManager.setup({
                type : 1,
                inactive : function(){
                    broadcaster.Disconnect();
                    broadcaster.heartBeat(false, false, false);
                    if(Messenger.common().modules.includes('ThreadManager'))ThreadManager.state().socketStatusCheck()

                },
                activate : function(){
                    if(CallManager.state().initialized){
                        window.location.reload();
                        return;
                    }
                    Messenger.xhr().payload({
                        route : Messenger.common().API + 'heartbeat',
                        data : {
                            away : false
                        },
                        success : function(data){
                            methods.manageHeartbeatData(data);
                            opt.settings.is_away = false;
                            broadcaster.heartBeat(true, false, false);
                            broadcaster.Echo(true);
                        },
                        fail : function(){
                            window.reload();
                        }
                    });
                }
            });
            InactivityManager.setup({
                type : 2,
                inactive : function(){
                    opt.settings.is_away = true;
                    if(!opt.socket.forced_disconnect) broadcaster.heartBeat(false, true, false)
                },
                activate : function(){
                    opt.settings.is_away = false;
                    if(!opt.socket.forced_disconnect) broadcaster.heartBeat(false, true, false)
                }
            })
        },
        setListeners : function(){
            $('.notify-drop').click(function(e){
                e.stopPropagation();
            });
            opt.elements.active_calls_link.click(methods.pullActiveCalls);
            opt.elements.click_friends_tab.click(function(){
                if($("#f_pending").hasClass('show')) methods.pullFriendRequest();
                if($("#f_sent").hasClass('show')) methods.pullSentFriendRequest();
            });
            $('#nav-friend-tabs a').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
                if(this.id === 'tab-pending') methods.pullFriendRequest();
                if(this.id === 'tab-sent') methods.pullSentFriendRequest();
            });
        }
    },
    broadcaster = {
        Echo : function(reconnected){
            if(!Messenger.common().websockets) return;
            opt.socket.forced_disconnect = false;
            opt.socket.Echo = new Echo({
                broadcaster : 'socket.io',
                host : Messenger.common().SOCKET,
            });
            opt.socket.Echo.connector.socket.on('connect', function(){
                opt.socket.socket_status = true;
                broadcaster.PrivateChannel(Messenger.common().id);
                if(reconnected) broadcaster.reconnected(true)
            });
            opt.socket.Echo.connector.socket.on('reconnect', broadcaster.reconnected);
            opt.socket.Echo.connector.socket.on('disconnect', function(){
                opt.socket.socket_status = false;
                if(Messenger.common().modules.includes('ThreadManager')) ThreadManager.state().socketStatusCheck();
                if(CallManager.state().initialized) CallManager.channel().disconnected()
            });
            opt.socket.Echo.connector.socket.on('subscription_error', broadcaster.subscriptionError)
        },
        reconnected : function(full){
            if(typeof full === "boolean" && !full) broadcaster.heartBeat(false, true, true);
            if(Messenger.common().modules.includes('ThreadManager')) ThreadManager.state().reConnected(typeof full === "boolean" && full);
            if(CallManager.state().initialized) CallManager.channel().reconnected(typeof full === "boolean" && full)
        },
        subscriptionError : function(e){
            let private_channel = /private-/i, presence_channel = /presence-/i;
            if(private_channel.test(e)){
                broadcaster.Disconnect();
                if(opt.socket.private_channel_retries === 2) return;
                opt.socket.private_channel_retries++;
                broadcaster.Echo(true)
            }
            if(presence_channel.test(e)){
                broadcaster.Disconnect();
                if(opt.socket.presence_channel_retries === 2){
                    opt.socket.private_channel_retries = 0;
                    broadcaster.Echo(false);
                    return;
                }
                opt.socket.presence_channel_retries++;
                broadcaster.Echo(true)
            }
        },
        Disconnect : function(){
            if(opt.socket.Echo !== null) opt.socket.Echo.disconnect();
            opt.socket.forced_disconnect = true;
            opt.socket.socket_status = false;
        },
        PrivateChannel : function(id){
            if(!opt.socket.Echo) return;
            if(typeof opt.socket.Echo.connector.channels['private-messenger.'+Messenger.common().model+'.'+id] !== 'undefined'){
                opt.socket.private_channel = opt.socket.Echo.connector.channels['private-messenger.'+Messenger.common().model+'.'+id];
                return;
            }
            opt.socket.private_channel = opt.socket.Echo.private('messenger.'+Messenger.common().model+'.'+id);
            opt.socket.private_channel.listen('.new.message', methods.incomingMessage)
            .listen('.thread.archived', methods.threadLeft)
            .listen('.message.archived', methods.messagePurged)
            .listen('.knock.knock', methods.incomingKnok)
            .listen('.new.thread', methods.newThread)
            .listen('.thread.approval', methods.threadApproval)
            .listen('.thread.left', methods.threadLeft)
            .listen('.incoming.call', methods.incomingCall)
            .listen('.joined.call', methods.callJoined)
            .listen('.left.call', methods.callLeft)
            .listen('.call.ended', methods.callEnded)
            .listen('.friend.request', methods.friendRequest)
            .listen('.friend.approved', methods.friendApproved)
            .listen('.friend.cancelled', methods.friendCancelled)
            .listen('.promoted.admin', methods.promotedAdmin)
            .listen('.demoted.admin', methods.demotedAdmin)
            .listen('.permissions.updated', methods.permissionsUpdated)
            .listen('.reaction.added', methods.incomingReact)
        },
        heartBeat : function(state, check, gather){
            let payload = function(){
                Heartbeat.update(opt.settings.is_away, methods.manageHeartbeatData, window.location.reload)
            };
            if(check){
                payload();
                return;
            }
            if(!state){
                clearInterval(opt.storage.heartbeat_interval);
                opt.storage.heartbeat_interval = null;
                return;
            }
            opt.storage.heartbeat_interval = setInterval(payload, 120000)
        }
    },
    Heartbeat = {
        gather : function(onPass, onFail){
            Messenger.xhr().request({
                route : Messenger.common().API + 'heartbeat',
                success : Heartbeat.manage,
                shared : {
                    onPass : onPass
                },
                fail : onFail
            })
        },
        update : function(state, onPass, onFail){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'heartbeat',
                data : {
                    away : state
                },
                success : Heartbeat.manage,
                shared : {
                    onPass : onPass
                },
                fail : onFail
            })
        },
        manage : function (data) {
            if("onPass" in data && typeof data.onPass === 'function'){
                data.onPass(data)
            }
        }
    },
    methods = {
        promotedAdmin : function(data){
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().promotedAdmin(data.thread_id,);
            }
        },
        demotedAdmin : function(data){
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().demotedAdmin(data.thread_id,);
            }
        },
        permissionsUpdated : function(data){
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().permissionsUpdated(data.thread_id,);
            }
        },
        friendRequest : function(pending){
            Messenger.alert().Alert({
                title : 'New friend request from ' + pending.sender.name,
                toast : true,
                theme : 'info'
            });
            broadcaster.heartBeat(false, true);
            methods.togglePageTitle('Friend request from ' + pending.sender.name);
            methods.playAlertSound('notify');
        },
        friendApproved : function(friend){
            Messenger.alert().Alert({
                title : friend.sender.name + ' approved your friend request!',
                toast : true,
                theme : 'success'
            });
            methods.togglePageTitle(friend.sender.name + ' is now your friend');
            methods.playAlertSound('notify');
        },
        friendCancelled : function(){
            broadcaster.heartBeat(false, true);
        },
        incomingCall : function(call){
            if(CallManager.state().initialized || !opt.settings.notifications) return;
            broadcaster.heartBeat(false, true, true);
            methods.togglePageTitle(call.sender.name+' is calling');
            CallManager.newCall(call)
        },
        callEnded : function(call){
            if(!opt.settings.notifications) return;
            CallManager.callEnded(call);
            broadcaster.heartBeat(false, true, true);
        },
        callJoined : function(call){
            if(CallManager.state().initialized || !opt.settings.notifications) return;
            opt.storage.last_call_update = call.thread_id;
            CallManager.joined(call);
            broadcaster.heartBeat(false, true, true);
        },
        callLeft : function(call){
            if(CallManager.state().initialized || !opt.settings.notifications) return;
            CallManager.left(call);
            broadcaster.heartBeat(false, true, true);
        },
        incomingMessage : function(data){
            if(!opt.settings.notifications) return;
            let runTitle = function(){
                methods.togglePageTitle(data.owner.name+' says...');
            },
            myself = Messenger.common().id === data.owner_id;
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().newMessage(data);
                if(!myself) runTitle();
                return;
            }
            if(CallManager.state().initialized || myself) return;
            runTitle();
            broadcaster.heartBeat(false, true, true);
            methods.playAlertSound('message');
            if(![0,1,2].includes(data.type) || !opt.settings.message_popups) return;
            let body = null;
            switch(data.type){
                case 0:
                    body = data.body.length > 45 ? Messenger.format().shortcodeToImage(data.body.substring(0, 42) + "...") : Messenger.format().shortcodeToImage(data.body);
                break;
                case 1:
                    body = "Sent an image";
                break;
                case 2:
                    body = "Sent a file";
                break;
            }
            Messenger.alert().Alert({
                title : (data.meta.thread_type === 2 ? data.meta.thread_name : data.owner.name),
                body : body,
                toast : true,
                theme : 'info',
                toast_options : {
                    onclick : function(){
                        window.location.href = Messenger.common().WEB + '/'+data.thread_id
                    },
                    timeOut : 5000
                }
            })
        },
        incomingReact : function(data){
            if(!opt.settings.notifications || CallManager.state().initialized) return;
            if(!Messenger.common().modules.includes('ThreadManager') || ThreadManager.state().thread_id !== data.message.thread_id){
                Messenger.alert().Alert({
                    title : Messenger.format().shortcodeToImage(data.reaction)+' '+data.owner.name+' reacted',
                    toast : true,
                    theme : 'info',
                    toast_options : {
                        onclick : function(){
                            if(Messenger.common().modules.includes('ThreadManager')){
                                ThreadManager.load().initiate_thread({thread_id : data.message.thread_id})
                            }
                            else {
                                window.location.href = Messenger.common().WEB + '/'+data.message.thread_id
                            }
                        },
                        timeOut : 5000
                    }
                });
                methods.togglePageTitle(data.owner.name+' reacted...');
                methods.playAlertSound('notify');
            }
        },
        incomingKnok : function(data){
            if(!opt.settings.notifications) return;
            let name = data.thread.group ? data.thread.name : data.sender.name;
            let avatar = data.thread.group ? data.thread.avatar.md : data.sender.avatar.md;
            if(CallManager.state().initialized){
                if(CallManager.state().thread_id === data.thread.id){
                    methods.playAlertSound('knok');
                    methods.togglePageTitle(name+' is knocking...');
                }
                return;
            }
            if(Messenger.common().modules.includes('ThreadManager') && ThreadManager.state().thread_id === data.thread.id){
                methods.playAlertSound('knok');
                methods.togglePageTitle(name+' is knocking...');
                return;
            }
            Messenger.alert().Modal({
                wait_for_others : true,
                theme : 'dark',
                icon : 'hand-rock',
                size : 'sm',
                centered : true,
                title : 'Knock Knock',
                body : '<div class="col-12 mb-3"><div class="text-center text-dark"><div id="knok_animate"><i  class="fas fa-hand-rock fa-7x"></i></div></div></div>' +
                    '<div class="col-12 text-center"> <img height="25" width="25" class="mr-2 rounded-circle" src="'+avatar+'" /><span class="h6 font-weight-bold">'+name+'</span></div>',
                onReady : function(){
                    methods.playAlertSound('knok');
                    methods.togglePageTitle(name+' is knocking...');
                    PageListeners.listen().animateKnok(true)
                },
                cb_btn_txt : 'View',
                cb_btn_icon : 'comment-dots',
                cb_btn_theme : 'success',
                onClose : function(){
                    PageListeners.listen().animateKnok(false)
                },
                callback : function(){
                    if(Messenger.common().modules.includes('ThreadManager')){
                        ThreadManager.load().initiate_thread({thread_id : data.thread.id});
                        return;
                    }
                    window.location.href = Messenger.common().WEB + '/'+data.thread.id
                },
                cb_close : true,
                timer : 15000
            })
        },
        newThread : function(data){
            if(!opt.settings.notifications) return;
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().addedToThread(data.thread.id);
            }
            broadcaster.heartBeat(false, true, true);
            methods.playAlertSound('message');
            if(data.thread.group){
                Messenger.alert().Alert({
                    title : data.thread.name,
                    body : data.sender.name+' added you to the group!',
                    toast : true,
                    theme : 'info'
                })
            }
            else{
                Messenger.alert().Alert({
                    title : data.sender.name+' started a conversation with you!',
                    body : (data.thread.pending ? 'You must accept or deny the message request' : ''),
                    toast : true,
                    theme : 'info'
                })
            }
        },
        threadApproval : function(data){
            if(!opt.settings.notifications) return;
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().threadApproval(data.thread.id, data.thread.approved);
            }
            if(data.thread.approved){
                methods.playAlertSound('message');
                Messenger.alert().Alert({
                    title : data.sender.name+' accepted your message request!',
                    toast : true,
                    theme : 'info'
                })
            }
        },
        messagePurged : function(data){
            if(!opt.settings.notifications) return;
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().purgeMessage(data);
            }
        },
        threadLeft : function(data){
            if(!opt.settings.notifications) return;
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().threadLeft(data.thread_id);
            }
        },
        manageMessageCounts : function(data){
            opt.storage.unread_thread = data.total_unread;
            methods.updatePageStates()
        },
        manageHeartbeatData : function(data){
            opt.settings.global_away = data.online_status === 2;
            opt.storage.unread_notify = 0;
            opt.storage.unread_thread = data.unread_threads_count;
            opt.storage.active_calls_count = data.active_calls_count;
            if(data.active_calls_count === 0){
                opt.storage.active_calls = [];
            }
            else{
                opt.elements.active_call_nav_icon.addClass('glowing_text_warning');
            }
            opt.storage.pending_friends_count = data.pending_friends_count;
            if(Messenger.common().modules.includes('ThreadManager')) ThreadManager.state().online(opt.settings.global_away ? 2 : 1);
            methods.updatePageStates();
        },
        updatePageStates : function(){
            methods.updateTitle();
            if(opt.storage.active_calls_count === 0){
                opt.elements.active_calls_ctnr.html(templates.no_calls());
                opt.elements.active_call_nav_icon.removeClass('glowing_text_warning');
            }
            opt.storage.unread_thread > 0 ? opt.elements.thread_count_area.html(opt.storage.unread_thread) : opt.elements.thread_count_area.html('');
            opt.storage.pending_friends_count > 0 ? opt.elements.pending_friends_count_area.html(opt.storage.pending_friends_count) : opt.elements.pending_friends_count_area.html('');
            opt.storage.active_calls_count > 0 ? opt.elements.calls_count_area.html(opt.storage.active_calls_count) : opt.elements.calls_count_area.html('');
            if(opt.storage.unread_thread > 0 || opt.storage.pending_friends_count > 0 || opt.storage.active_calls_count > 0){
                opt.elements.mobile_nav_count_area.html(opt.storage.unread_thread+opt.storage.pending_friends_count+opt.storage.active_calls_count);
                return;
            }
            opt.elements.mobile_nav_count_area.html('')
        },
        updateActiveCalls : function(calls){
            if(CallManager.state().initialized) return;
            if(!calls.length){
                opt.storage.active_calls = [];
                opt.storage.active_calls_count  = 0;
                opt.elements.active_calls_ctnr.html(templates.no_calls());
                opt.elements.active_call_nav_icon.removeClass('glowing_text_warning');
                methods.updatePageStates();
                return;
            }
            opt.storage.active_calls = calls;
            opt.elements.active_calls_ctnr.html('');
            opt.storage.active_calls.forEach(function(call){
                opt.elements.active_calls_ctnr.append(templates.active_call(call))
            });
            opt.elements.active_call_nav_icon.addClass('glowing_text_warning');
        },
        updatePendingFriends : function(data){
            if(!data.length){
                opt.storage.pending_friends_count = 0;
                opt.elements.pending_friends_ctnr.html('<div class="col-12 text-center h5 mt-2"><span class="badge badge-pill badge-light shadow-sm"><i class="fas fa-user-friends"></i> No Pending Request</span></div>');
                methods.updatePageStates();
                return;
            }
            opt.storage.pending_friends = data;
            opt.storage.pending_friends_count = opt.storage.pending_friends.length;
            methods.updatePageStates();
            opt.elements.pending_friends_ctnr.html('');
            opt.storage.pending_friends.forEach(function(friend){
                opt.elements.pending_friends_ctnr.append(templates.pending_friend(friend))
            })
        },
        updateSentFriends : function(data){
            if(!data.length){
                opt.storage.sent_friends = [];
                opt.elements.sent_friends_ctrn.html('<div class="col-12 text-center h5 mt-2"><span class="badge badge-pill badge-light shadow-sm"><i class="fas fa-user-friends"></i> No Sent Request</span></div>');
                return;
            }
            opt.storage.sent_friends = data;
            opt.elements.sent_friends_ctrn.html('');
            opt.storage.sent_friends.forEach(function(friend){
                opt.elements.sent_friends_ctrn.append(templates.sent_friend(friend))
            })
        },
        updateTitle : function(){
            let total = opt.storage.unread_thread+opt.storage.pending_friends_count;
            if(opt.storage.active_calls_count > 0 && !CallManager.state().initialized) total = total+opt.storage.active_calls_count;
            if(total > 0){
                let the_title = '('+total+') '+opt.storage.original_title;
                opt.storage.current_title = the_title;
                document.title = the_title;
                return;
            }
            document.title = opt.storage.original_title;
            opt.storage.current_title = opt.storage.original_title;
        },
        togglePageTitle : function(msg){
            methods.pageTitle(false);
            if(!document.hasFocus()){
                methods.pageTitle(true, msg);
                $(document).one("click", function(){
                    methods.pageTitle(false);
                })
            }
        },
        pageTitle : function(power, msg){
            if(power){
                opt.storage.toggle_title_interval = setInterval(function () {
                    document.title = (document.title.trim() === opt.storage.current_title.trim() ? msg : opt.storage.current_title);
                }, 3000);
                return;
            }
            if(opt.storage.toggle_title_interval) clearInterval(opt.storage.toggle_title_interval);
            opt.storage.toggle_title_interval = null;
            methods.updateTitle()
        },
        pullSentFriendRequest : function(){
            Messenger.xhr().request({
                route : Messenger.common().API + 'friends/sent',
                success : methods.updateSentFriends,
                fail_alert : true
            })
        },
        pullFriendRequest : function(){
            Messenger.xhr().request({
                route : Messenger.common().API + 'friends/pending',
                success : methods.updatePendingFriends,
                fail_alert : true
            })
        },
        pullActiveCalls : function(){
            Messenger.xhr().request({
                route : Messenger.common().API + 'active-calls',
                success : methods.updateActiveCalls,
                fail_alert : true
            })
        },
        settingsToggle : function(arg){
            if("message_popups" in arg) opt.settings.message_popups = arg.message_popups;
            if("message_sound" in arg) opt.settings.message_sound = arg.message_sound;
            if("notify_sound" in arg) opt.settings.notify_sound = arg.notify_sound;
            if("call_ringtone_sound" in arg) opt.settings.call_ringtone_sound = arg.call_ringtone_sound;
            if("notifications" in arg) opt.settings.notifications = arg.notifications;
        },
        playAlertSound : function(type){
            let soundOff = function () {
                opt.settings.sound_playing = false;
            };
            try{
                switch(type){
                    case 'message':
                        opt.sounds.message_sound_file.volume = 0.2;
                        if(!opt.settings.message_sound || opt.settings.sound_playing) return;
                        opt.settings.sound_playing = true;
                        opt.sounds.message_sound_file.play().then(soundOff).catch(soundOff);
                    break;
                    case 'notify':
                        if(!opt.settings.notify_sound || opt.settings.sound_playing) return;
                        opt.settings.sound_playing = true;
                        opt.sounds.notify_sound_file.play().then(soundOff).catch(soundOff);
                    break;
                    case 'call':
                        if(!opt.settings.call_ringtone_sound || opt.settings.sound_playing) return;
                        opt.settings.sound_playing = true;
                        opt.sounds.call_sound_file.play().then(soundOff).catch(soundOff);
                    break;
                    case 'knok':
                        if(opt.settings.sound_playing) return;
                        opt.settings.sound_playing = true;
                        opt.sounds.knok_sound_file.play().then(soundOff).catch(soundOff);
                    break;
                }
            }catch (e) {
                console.log(e)
            }
        },
        callAction : function (id) {
            if(!opt.storage.active_calls || !opt.storage.active_calls.length) return;
            for(let i = 0; i < opt.storage.active_calls.length; i++) {
                if (opt.storage.active_calls[i].id === id) {
                    CallManager.join(opt.storage.active_calls[i]);
                    break;
                }
            }
        },
        pendingFriendAction : function (id, action) {
            for(let i = 0; i < opt.storage.pending_friends.length; i++) {
                if(opt.storage.pending_friends[i].id === id){
                    $("#friend_actions_"+id).remove();
                    FriendsManager.action({
                        action : action,
                        provider_id : opt.storage.pending_friends[i].sender.provider_id,
                        pending_friend_id : opt.storage.pending_friends[i].id
                    });
                    break;
                }
            }
        },
        sentFriendCancel : function (id) {
            for(let i = 0; i < opt.storage.sent_friends.length; i++) {
                if(opt.storage.sent_friends[i].id === id){
                    $("#friend_actions_"+id).remove();
                    FriendsManager.action({
                        action : 'cancel',
                        provider_id : opt.storage.sent_friends[i].recipient.provider_id,
                        sent_friend_id : opt.storage.sent_friends[i].id
                    });
                    break;
                }
            }
        },
        setNewTitle : function(title){
            opt.storage.original_title = title;
            methods.updateTitle();
        }
    },
    templates = {
        active_call : function (call) {
            let bg = 'warning', color = 'dark', type = 'Call',
                msg = 'Click to join', action = "NotifyManager.calls('"+call.id+"');",
                icon = '<i class="fas fa-video"></i>';
            if(call.options.in_call){
                bg = 'danger';
                color = 'light';
                msg = 'Currently in '+type;
            }
            else if(call.options.left_call){
                bg = 'secondary';
                color = 'light';
                msg = 'Click to rejoin';
            }
            return '<a onclick="'+action+' return false;" href="#" class="list-group-item list-group-item-action p-2 text-'+color+' bg-'+bg+'">\n' +
                '    <div class="media">\n' +
                '        <div class="media-left media-top">\n' +
                '            <img class="rounded media-object" height="50" width="50" src="'+call.meta.thread_avatar.sm+'">\n' +
                '        </div>\n' +
                '        <div class="media-body">\n' +
                '            <h6 class="ml-2 mb-1 font-weight-bold">'+icon+' '+call.meta.thread_name+' - '+type+'</h6>\n' +
                '            <div class="mt-2"><span class="float-right"><span class="badge badge-pill badge-light">'+icon+' '+msg+'</span></span></div>\n' +
                '        </div>\n' +
                '    </div>\n' +
                '</a>'
        },
        no_calls : function(){
            return '<div class="col-12 text-center h5 mt-2"><span class="badge badge-pill badge-light shadow-sm"><i class="fas fa-video"></i> No Active Calls</span></div>';
        },
        pending_friend : function (friend) {
            return '<a onclick="return false;" href="#" class="list-group-item list-group-item-action p-2 text-dark bg-light">\n' +
                '    <div class="media">\n' +
                '        <div class="media-left media-top" '+(friend.sender.route ? 'onclick="window.location.href=\''+friend.sender.route+'\'"' : '')+'>\n' +
                '            <img class="rounded media-object" height="50" width="50" src="'+friend.sender.avatar.sm+'">\n' +
                '        </div>\n' +
                '        <div class="media-body">\n' +
                '        <span class="mt-n1 float-right small">'+Messenger.format().makeTimeAgo(friend.created_at)+' <i class="far fa-clock"></i></span>'+
                '            <h6 '+(friend.sender.route ? 'onclick="window.location.href=\''+friend.sender.route+'\'"' : '')+' class="ml-2 mb-1 font-weight-bold">'+friend.sender.name+'</h6>\n' +
                '            <div id="friend_actions_'+friend.id+'" class="mt-2 col-12 px-0">' +
                '               <span class="float-right">' +
                '                   <button title="Accept friend request" onclick="NotifyManager.pendingFriends(\''+friend.id+'\', \'accept\')" class="btn btn-sm btn-success pt-1 pb-0 px-1"><i class="h5 far fa-check-circle"></i></button>' +
                '                   <button title="Deny friend request" onclick="NotifyManager.pendingFriends(\''+friend.id+'\', \'deny\')" class="btn btn-sm btn-danger mx-1 pt-1 pb-0 px-1"><i class="h5 fas fa-ban"></i></button>' +
                '               </span>' +
                '            </div>\n' +
                '        </div>\n' +
                '    </div>\n' +
                '</a>'
        },
        sent_friend : function (friend) {
            return '<a onclick="return false;" href="#" class="list-group-item list-group-item-action p-2 text-dark bg-light">\n' +
                '    <div class="media">\n' +
                '        <div class="media-left media-top" '+(friend.recipient.route ? 'onclick="window.location.href=\''+friend.recipient.route+'\'"' : '')+'>\n' +
                '            <img class="rounded media-object" height="50" width="50" src="'+friend.recipient.avatar.sm+'">\n' +
                '        </div>\n' +
                '        <div class="media-body">\n' +
                '        <span class="mt-n1 float-right small">'+Messenger.format().makeTimeAgo(friend.created_at)+' <i class="far fa-clock"></i></span>'+
                '            <h6 '+(friend.recipient.route ? 'onclick="window.location.href=\''+friend.recipient.route+'\'"' : '')+' class="ml-2 mb-1 font-weight-bold">'+friend.recipient.name+'</h6>\n' +
                '            <div id="friend_actions_'+friend.id+'" class="mt-2 col-12 px-0">' +
                '               <span class="float-right">' +
                '                   <button title="Cancel friend request" onclick="NotifyManager.cancelFriend(\''+friend.id+'\')" class="btn btn-sm btn-danger mx-1 pt-1 pb-0 px-1"><i class="h5 fas fa-ban"></i></button>' +
                '               </span>' +
                '            </div>\n' +
                '        </div>\n' +
                '    </div>\n' +
                '</a>'
        }
    };
    return {
        init : Initialize.Init,
        newMessage : methods.incomingMessage,
        updateMessageCount : methods.manageMessageCounts,
        sound : methods.playAlertSound,
        settings : methods.settingsToggle,
        calls : methods.callAction,
        pendingFriends : methods.pendingFriendAction,
        cancelFriend : methods.sentFriendCancel,
        friendsPending : methods.pullFriendRequest,
        sentFriends : methods.pullSentFriendRequest,
        activeCalls : methods.pullActiveCalls,
        setTitle : methods.setNewTitle,
        heartbeat : function(){
            broadcaster.heartBeat(false, true, true);
        },
        counts : function(){
            return {
                notify : opt.storage.unread_notify,
                threads : opt.storage.unread_thread
            }
        },
        sockets : function(){
            return {
                forced_disconnect : opt.socket.forced_disconnect,
                status : opt.socket.socket_status,
                Echo : opt.socket.Echo,
                away : opt.settings.away(),
                disconnect : broadcaster.Disconnect
            }
        }
    };
}());