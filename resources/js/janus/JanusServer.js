import Janus from '../Janus';

window.JanusServer = (function () {
    var opt = {
        TUTORIAL : false,
        DEMO : false,
        initialized : false,
        lock : true,
        my_stream_ctnr : $("#my_video_ctrn"),
        other_stream_ctnr : $("#other_videos_ctrn"),
        empty_room : $("#empty_room"),
        settings : {
            _mode : 'default',
            mode : 'default',
            bitrate : 128000,
            audio : true,
            video : true,
            screen : false,
            muted : false,
            video_paused : false,
            active_speaker : false,
            simulcast_1 : false,
            simulcast_2 : false,
            video_constraints : {
                term : 'stdres',
                width : {ideal : 1280},
                height : {ideal : 720},
                facingMode : 'user'
            }
        },
        speech_events : {
            analyzer : null,
            speaker_locked : false,
            speaker_id : null,
            screen_share_id : null,
            speaker_timeout : null,
            activeLockId : function () {
                return this.screen_share_id ? this.screen_share_id : this.speaker_id
            }
        },
        server : {
            JANUS : null,
            SFU : null,
            call_socket : null,
            api_secret : null,
            room_joined : false,
            retries : 0,
            main : [],
            ice : []
        },
        storage : {
            display : null,
            my_janus_id : null,
            my_janus_private_id : null,
            broadcast_stream : null,
            feeds : [],
            participants : [],
            bitrateTimer : []
        }
    },
    Config = {
        init : function(demo){
            if(opt.initialized) return;
            opt.lock = false;
            if(demo) opt.DEMO = true;
            opt.server.api_secret = CallManager.state().janus_secret;
            opt.server.main = CallManager.state().janus_main;
            opt.server.ice = CallManager.state().janus_ice;
            opt.storage.display = {
                id : Messenger.common().id,
                name : Messenger.common().name,
                avatar : Messenger.common().avatar_md
            };
            if(!opt.DEMO){
                switch(CallManager.state().thread_type){
                    case 1:
                        opt.settings.bitrate = 1024000;
                    break;
                    case 2:
                        opt.settings.bitrate = 600000;
                    break;
                }
            }
            if(CallManager.state().call_type === 1) opt.settings.video_constraints.term = 'hires-16:9';
            if(opt.DEMO) return;
            opt.initialized = true;
            Janus.init({
                debug : CallManager.state().janus_debug,
                callback : Config.setup
            });
            Sockets.setup()
        },
        reset : function(){
            opt.storage = {
                display : opt.storage.display,
                my_janus_id : null,
                my_janus_private_id : null,
                broadcast_stream : null,
                feeds : [],
                participants : [],
                bitrateTimer : []
            };
            opt.settings.screen = false;
            opt.settings.muted = false;
            opt.settings.video_paused = false;
            opt.my_stream_ctnr.html('');
            opt.other_stream_ctnr.html('');
        },
        demo : function(){
            if(opt.TUTORIAL) return;
            opt.initialized = true;
            PageListeners.listen().disposeTooltips();
            opt.other_stream_ctnr.show();
            opt.empty_room.show();
            Janus.init({
                debug : CallManager.state().janus_debug,
                callback : Config.setup
            });
        },
        setup : function () {
            if(!Janus.isWebrtcSupported()) {
                Janus.log('No WebRTC Support');
                Messenger.alert().Alert({
                    toast : true,
                    theme : 'error',
                    title : 'It seems your browser does not support WebRTC. Unable to continue loading streams'
                });
                return;
            }
            if(opt.server.JANUS) return;
            Config.reset();
            if(opt.settings._mode === 'unpublished'){
                opt.settings.mode = 'unpublished';
                opt.my_stream_ctnr.html(templates.observing()).show();
            }
            else{
                opt.settings.mode = 'default';
                opt.my_stream_ctnr.html(templates.loading_media()).show();
            }
            opt.other_stream_ctnr.show();
            opt.empty_room.show();
            opt.server.JANUS = new Janus({
                server : opt.server.main,
                opaqueId : Messenger.common().id,
                apisecret : opt.server.api_secret,
                iceServers : opt.server.ice,
                success : Attach.myself,
                error : function(error) {
                    Janus.error(error);
                    Messenger.alert().Alert({
                        toast : true,
                        theme : 'error',
                        title : 'Unable to connect to our streaming services, please reload and try again'
                    });
                },
                destroyed : function() {
                    Janus.log('destroyed');
                    Config.destroy(true)
                }
            });
        },
        destroy : function (destroyed, callback) {
            if(!destroyed && opt.server.JANUS){
                opt.my_stream_ctnr.hide();
                Config.reset();
                if(opt.server.SFU) opt.server.SFU.hangup();
                setTimeout(opt.server.JANUS.destroy, 500)
            }
            opt.other_stream_ctnr.html('').hide();
            opt.empty_room.hide();
            opt.settings.mode = 'destroyed';
            methods.toolbarState();
            if(destroyed){
                opt.server.JANUS = null;
                opt.server.SFU = null;
            }
            if(callback && typeof callback === 'function') callback()
        },
        demoEnded : function(){
            opt.initialized = false;
            Config.destroy(false, function(){
                opt.my_stream_ctnr.html('<div class="h5 p-2 text-danger">This demo streaming room has been destroyed. If you would like to rejoin, please reload your page</div>').show();
            });
        }
    },
    Sockets = {
        setup : function(){
            if(!CallManager.channel().state || !CallManager.channel().socket){
                setTimeout(Sockets.setup, 1000);
                return;
            }
            opt.server.call_socket = CallManager.channel().socket;
            opt.server.call_socket.listenForWhisper('screen_share_started', methods.maximizePublisher)
            //     .listenForWhisper('screen_share_ended', methods.minimizePublisher)
        },
        joining : function (user) {
            Janus.log("User joined socket", user);
        },
        leaving : function (user) {
            Janus.log("User left socket", user);
        },
        disconnected : function(){
            // if(!opt.initialized || CallManager.state().processing) return;
            // Messenger.alert().Alert({
            //     toast : true,
            //     close_toast : true,
            //     theme : 'warning',
            //     title : 'You may be experiencing connection issues, your video streams may become interrupted'
            // });
        },
        reconnected : function(){
            // if(!opt.initialized) return;
            // Messenger.alert().Alert({
            //     toast : true,
            //     close_toast : true,
            //     theme : 'success',
            //     title : 'Reconnected'
            // });
        }
    },
    Attach = {
        myself : function () {
            opt.server.JANUS.attach({
                plugin : "janus.plugin.videoroom",
                success : function(pluginHandle) {
                    opt.server.SFU = pluginHandle;
                    Janus.log("Plugin attached! (" + opt.server.SFU.getPlugin() + ", id=" + opt.server.SFU.getId() + ")");
                    Janus.log("  -- This is a publisher/manager");
                    opt.server.SFU.send({
                        message : {
                            request : "join",
                            room : CallManager.state().room_id,
                            ptype : "publisher",
                            display : JSON.stringify(opt.storage.display),
                            pin : CallManager.state().room_pin
                        }
                    });
                    methods.toolbarState()
                },
                error : function(error) {
                    Janus.error("  -- Error attaching plugin...", error);
                },
                consentDialog: function(on) {
                    Janus.debug("Consent dialog should be " + (on ? "on" : "off") + " now");
                },
                mediaState: function(medium, on) {
                    Janus.log("Janus " + (on ? "started" : "stopped") + " receiving our " + medium);
                },
                webrtcState: function(on) {
                    Janus.log("Janus says our WebRTC PeerConnection is " + (on ? "up" : "down") + " now");
                },
                onmessage: function(msg, jsep) {
                    Janus.debug(" ::: Got a message (publisher) :::");
                    Janus.debug(msg);
                    let event = msg["videoroom"];
                    Janus.debug("Event: " + event);
                    if(event !== undefined && event !== null) {
                        if(event === "joined") {
                            // Publisher/manager created, negotiate WebRTC and attach to existing feeds, if any
                            opt.storage.my_janus_id = msg["id"];
                            opt.storage.my_janus_private_id = msg["private_id"];
                            Janus.log("Successfully joined room " + msg["room"] + " with ID " + opt.storage.my_janus_id);
                            methods.publishOwnFeed();
                            // Any new feed to attach to?
                            if(msg["publishers"] !== undefined && msg["publishers"] !== null) {
                                let list = msg["publishers"];
                                Janus.debug("Got a list of available publishers/feeds:");
                                Janus.debug(list);
                                for(let f in list) {
                                    if (!list.hasOwnProperty(f)) continue;
                                    let obj = {
                                        id : list[f]["id"],
                                        display : list[f]["display"],
                                        audio : list[f]["audio_codec"],
                                        video : list[f]["video_codec"]
                                    };
                                    Janus.debug("  >> [" + obj.id + "] " + obj.display + " (audio: " + obj.audio + ", video: " + obj.video + ")");
                                    Attach.remote(obj.id, obj.display, obj.audio, obj.video);
                                }
                            }
                            //get room participants on join
                            methods.getParticipants();

                        } else if(event === "talking"){
                            Janus.log('talking event :)')
                        }
                        else if(event === "stopped-talking"){
                            Janus.log('stopped talking event :(')
                        }
                        else if(event === "destroyed") {
                            Janus.warn("The room has been destroyed!");
                            if(opt.DEMO) Config.demoEnded();
                        } else if(event === "event") {
                            // Any new feed to attach to?
                            if(msg["publishers"] !== undefined && msg["publishers"] !== null) {
                                let list = msg["publishers"];
                                Janus.debug("Got a list of available publishers/feeds:");
                                Janus.debug(list);
                                for(let f in list) {
                                    if (!list.hasOwnProperty(f)) continue;
                                    let obj = {
                                        id : list[f]["id"],
                                        display : list[f]["display"],
                                        audio : list[f]["audio_codec"],
                                        video : list[f]["video_codec"]
                                    };
                                    Janus.debug("  >> [" + obj.id + "] " + obj.display + " (audio: " + obj.audio + ", video: " + obj.video + ")");
                                    Attach.remote(obj.id, obj.display, obj.audio, obj.video);
                                }
                            }
                            else if(msg["joining"] !== undefined && msg["joining"] !== null) {
                                // A participant joined the room
                                let joining = msg["joining"];
                                Janus.log("Participant Joined: " + joining['id']);
                                methods.addParticipant(joining);
                                methods.drawParticipants()
                            }
                            else if(msg["leaving"] !== undefined && msg["leaving"] !== null) {
                                // One of the publishers has gone away?
                                let leaving_id = msg["leaving"], remote_feed = null, remote_feed_storage = methods.locateStorageItem('feed', leaving_id);
                                Janus.log("Publisher left: " + leaving_id);
                                if(remote_feed_storage.found) {
                                    remote_feed = opt.storage.feeds[remote_feed_storage.index];
                                    Janus.debug("Feed " + remote_feed.feed_id + " (" + remote_feed.feed_name + ") has left the room, detaching");
                                    $("#other_stream_ctnr_"+remote_feed.feed_id).remove();

                                    remote_feed.detach();
                                    opt.storage.feeds.splice(remote_feed_storage.index, 1);
                                }
                                methods.removeParticipant(leaving_id);
                                if(opt.initialized) methods.drawParticipants();
                            }
                            else if(msg["unpublished"] !== undefined && msg["unpublished"] !== null) {
                                // One of the publishers has unpublished?
                                let unpublished_id = msg["unpublished"], remote_feed = null, remote_feed_storage = methods.locateStorageItem('feed', unpublished_id);
                                Janus.log("Publisher left: " + unpublished_id);
                                if(unpublished_id === 'ok') {
                                    // That's us
                                    opt.server.SFU.hangup();
                                    return;
                                }
                                if(remote_feed_storage.found) {
                                    remote_feed = opt.storage.feeds[remote_feed_storage.index];
                                    Janus.debug("Feed " + remote_feed.feed_id + " (" + remote_feed.feed_name + ") has left the room, detaching");
                                    $("#other_stream_ctnr_"+remote_feed.feed_id).remove();
                                    remote_feed.detach();
                                    opt.storage.feeds.splice(remote_feed_storage.index, 1);
                                    if(opt.initialized) methods.drawParticipants();
                                }
                            }
                            else if(msg["error"] !== undefined && msg["error"] !== null) {
                                Janus.error(msg["error"]);
                                if(msg["error_code"] !== undefined && msg["error_code"] === 426){
                                    Messenger.alert().Alert({
                                        toast : true,
                                        theme : 'error',
                                        title : 'Unable to join the streaming room. This session may have ended'
                                    });

                                }
                                else if(!opt.DEMO){
                                    Messenger.alert().Alert({
                                        toast : true,
                                        theme : 'error',
                                        title : msg["error"]
                                    });
                                }
                                if(opt.DEMO) Config.demoEnded();
                            }
                        }
                    }
                    if(jsep !== undefined && jsep !== null) {
                        Janus.debug("Handling SDP as well...");
                        Janus.debug(jsep);
                        opt.server.SFU.handleRemoteJsep({jsep: jsep});
                        // Check if any of the media we wanted to publish has
                        // been rejected (e.g., wrong or unsupported codec)
                        let audio = msg["audio_codec"], video = msg["video_codec"];
                        if(opt.storage.broadcast_stream && opt.storage.broadcast_stream.getAudioTracks() && opt.storage.broadcast_stream.getAudioTracks().length > 0 && !audio) {
                            // Audio has been rejected
                            toastr.warning("Our audio stream has been rejected, viewers won't hear us");
                        }
                        if(opt.storage.broadcast_stream && opt.storage.broadcast_stream.getVideoTracks() && opt.storage.broadcast_stream.getVideoTracks().length > 0 && !video) {
                            // Video has been rejected
                            toastr.warning("Our video stream has been rejected, viewers won't see us");
                            // Hide the webcam video
                        }
                    }
                },
                onlocalstream: function(stream) {
                    Janus.debug(" ::: Got a local stream :::");
                    opt.storage.broadcast_stream = stream;
                    Janus.debug(stream);
                    opt.my_stream_ctnr.html(templates.my_video_stream()).show();
                    let video_elm = document.getElementById('my_stream_src');
                    Janus.attachMediaStream(video_elm, stream);
                    if(opt.server.SFU.webrtcStuff.pc.iceConnectionState !== "completed" &&
                        opt.server.SFU.webrtcStuff.pc.iceConnectionState !== "connected") {
                        Janus.debug('publishing');
                    }
                    let videoTracks = stream.getVideoTracks();
                    if(videoTracks === null || videoTracks === undefined || videoTracks.length === 0) {
                        // No webcam
                        Janus.debug('no stream');
                    } else {
                        Janus.debug('stream shown');
                    }
                    methods.toolbarState();
                },
                onremotestream: function(stream) {
                    // The publisher stream is sendonly, we don't expect anything here
                },
                oncleanup: function() {
                    Janus.log(" ::: Got a cleanup notification: we are unpublished now :::");
                    opt.storage.broadcast_stream = null;
                    if(opt.settings.mode !== 'destroyed') opt.my_stream_ctnr.html(templates.observing());
                }
            });
        },
        remote : function (id, display, audio, video) {
            let remoteFeed = null;
            opt.server.JANUS.attach({
                plugin : "janus.plugin.videoroom",
                success: function(pluginHandle) {
                    remoteFeed = pluginHandle;
                    remoteFeed.simulcastStarted = false;
                    Janus.log("Plugin attached! (" + remoteFeed.getPlugin() + ", id=" + remoteFeed.getId() + ")");
                    Janus.log("  -- This is a subscriber");
                    let subscribe = {
                        request : "join",
                        room : CallManager.state().room_id,
                        ptype : "subscriber",
                        feed : id,
                        pin : CallManager.state().room_pin,
                        private_id : opt.storage.my_janus_private_id
                    };
                    if(Janus.webRTCAdapter.browserDetails.browser === "safari" &&
                        (video === "vp9" || (video === "vp8" && !Janus.safariVp8))) {
                        if(video) video = video.toUpperCase();
                        toastr.warning("Publisher is using " + video + ", but Safari doesn't support it: disabling video");
                        subscribe["offer_video"] = false;
                    }
                    remoteFeed.videoCodec = video;
                    remoteFeed.send({"message": subscribe});
                },
                error: function(error) {
                    Janus.error("  -- Error attaching plugin...", error);
                },
                onmessage: function(msg, jsep) {
                    Janus.debug(" ::: Got a message (subscriber) :::");
                    Janus.debug(msg);
                    let event = msg["videoroom"];
                    Janus.debug("Event: " + event);
                    if(msg["error"] !== undefined && msg["error"] !== null) {
                        Janus.error(msg["error"]);
                    } else if(event !== undefined && event !== null) {
                        if(event === "attached") {
                            let info = JSON.parse(''+msg["display"]+'');
                            remoteFeed.feed_id = msg["id"];
                            remoteFeed.feed_name = info.name;
                            remoteFeed.feed_avatar = info.avatar;
                            remoteFeed.feed_owner_id = info.id;
                            opt.storage.feeds.push(remoteFeed);
                            Janus.log("Successfully attached to feed " + remoteFeed.feed_id + " (" + remoteFeed.feed_name + ") in room " + msg["room"]);
                        } else if(event === "event") {
                            // Check if we got an event on a simulcast-related event from this publisher
                            Janus.debug(event);
                            let substream = msg["substream"], temporal = msg["temporal"];
                            if((substream !== null && substream !== undefined) || (temporal !== null && temporal !== undefined)) {
                                //manage simulcast buttons
                            }
                        } else {
                            // What has just happened?
                        }
                    }
                    if(jsep !== undefined && jsep !== null) {
                        Janus.debug("Handling SDP as well...");
                        Janus.debug(jsep);
                        // Answer and attach
                        remoteFeed.createAnswer(
                            {
                                jsep: jsep,
                                media: { audioSend: false, videoSend: false },
                                success: function(jsep) {
                                    Janus.debug("Got SDP!");
                                    Janus.debug(jsep);
                                    remoteFeed.send({
                                        message : {
                                            request : 'start',
                                            room : CallManager.state().room_id
                                        },
                                        jsep : jsep
                                    });
                                },
                                error: function(error) {
                                    Janus.error("WebRTC error:", error);
                                }
                            });
                    }
                },
                webrtcState: function(on) {
                    Janus.log("Janus says this WebRTC PeerConnection (feed #" + remoteFeed.feed_id + ") is " + (on ? "up" : "down") + " now");
                },
                onlocalstream: function(stream) {
                    // The subscriber stream is recvonly, we don't expect anything here
                },
                onremotestream: function(stream) {
                    if(!opt.initialized || opt.settings.mode === 'destroyed') return;
                    Janus.debug("Remote feed #" + remoteFeed.feed_id);
                    $('.observer-'+remoteFeed.feed_id).remove();
                    let video_elm = document.getElementById('other_stream_src_'+remoteFeed.feed_id),
                    refreshVideoElm = function(){
                        video_elm = document.getElementById('other_stream_src_'+remoteFeed.feed_id)
                    };
                    if(!video_elm){
                        opt.other_stream_ctnr.prepend(templates.other_video_stream(remoteFeed));
                        refreshVideoElm()
                    }
                    Janus.attachMediaStream(video_elm, stream);

                    video_elm.onpause = function(){
                        methods.manageStreamPlayIcon(remoteFeed.feed_id, true);
                        Janus.log('paused');
                    }
                    video_elm.onplay = function(){
                        methods.manageStreamPlayIcon(remoteFeed.feed_id, false);
                        Janus.log('playing');
                    }
                    video_elm.onvolumechange = function(){
                        methods.toggleStreamMute(remoteFeed.feed_id, true)
                    }
                    setTimeout(function () {
                        methods.manageStreamPlayIcon(remoteFeed.feed_id, video_elm.paused);
                        Janus.log('Is Paused??', video_elm.paused);
                    },1000)

                    opt.empty_room.hide();
                },
                oncleanup: function() {
                    if(!opt.initialized || opt.settings.mode === 'destroyed') return;
                    Janus.log(" ::: Got a cleanup notification (remote feed " + remoteFeed.feed_id + ") :::");
                    Janus.log("Publisher left: " + remoteFeed.feed_id);
                    let remote_feed_storage = methods.locateStorageItem('feed', remoteFeed.feed_id);
                    $("#other_stream_ctnr_" + remoteFeed.feed_id).remove();
                    if(remote_feed_storage.found){
                        opt.storage.feeds.splice(remote_feed_storage.index, 1);
                    }
                    // if(!opt.storage.feeds.length && opt.initialized) opt.empty_room.show();
                    if(opt.initialized) methods.drawParticipants()
                }
            });
        }
    },
    methods = {
        getParticipants : function(){
            opt.server.SFU.send({
                message : {
                    request : "listparticipants",
                    room : CallManager.state().room_id
                },
                success : function(msg){
                    opt.storage.participants = [];
                    let list = msg["participants"];
                    Janus.log(" ::: Got a participant listing");
                    Janus.log(list);
                    for(let f in list) {
                        if (!list.hasOwnProperty(f) || list[f]['id'] === opt.storage.my_janus_id) continue;
                        methods.addParticipant(list[f])
                    }
                    methods.drawParticipants()
                }
            });
        },
        addParticipant : function(participant){
            try{
                if(methods.locateStorageItem('participant', participant['id']).found) return;
                let info = JSON.parse(''+participant['display']+'');
                opt.storage.participants.push({
                    id : participant['id'],
                    name : info.name,
                    avatar : info.avatar,
                    owner_id : info.id,
                    publisher : participant['publisher']
                });
            }catch (e) {
                Janus.log(e);
            }
        },
        removeParticipant : function(id){
            let participant = methods.locateStorageItem('participant', id);
            if(participant.found){
                opt.storage.participants.splice(participant.index, 1);
            }
            $("#other_stream_ctnr_" + id).remove();
            methods.drawParticipants()
        },
        drawParticipants : function(){
            if(!opt.storage.participants.length){
                opt.empty_room.show();
                return;
            }
            opt.empty_room.hide();
            opt.storage.participants.forEach(function(participant){
                if($('#other_stream_src_'+participant.id).length || $('#other_stream_ctnr_'+participant.id).length) return;
                opt.other_stream_ctnr.append(templates.observer(participant));
            })
        },
        publishOwnFeed : function(opts, callback, fail) {
            opts = opts || {};
            opt.server.SFU.createOffer({
                media : {
                    audioRecv : false,
                    videoRecv : false,
                    removeAudio : opts.hasOwnProperty('removeAudio') ? opts.removeAudio : false,
                    removeVideo : opts.hasOwnProperty('removeVideo') ? opts.removeVideo : false,
                    replaceVideo : opts.hasOwnProperty('replaceVideo') ? opts.replaceVideo : false,
                    addAudio : opts.hasOwnProperty('addAudio') ? opts.addAudio : false,
                    addVideo : opts.hasOwnProperty('addVideo') ? opts.addVideo : false,
                    audioSend : opts.hasOwnProperty('audioSend') ? opts.audioSend : opt.settings.audio,
                    videoSend : opts.hasOwnProperty('videoSend') ? opts.videoSend : opt.settings.video,
                    video : opts.hasOwnProperty('video') ? opts.video : opt.settings.video_constraints.term,
                    screenshareFrameRate : opts.hasOwnProperty('screenshareFrameRate') ? opts.screenshareFrameRate : null
                },
                simulcast : opt.settings.simulcast_1,
                simulcast2 : opt.settings.simulcast_2,
                success: function(jsep) {
                    if(opts.mode) opt.settings.mode = opts.mode;
                    Janus.debug("Got publisher SDP!");
                    Janus.debug(jsep);
                    let message = {
                        request : 'configure',
                        audio : opts.hasOwnProperty('audioSend') ? opts.audioSend : opt.settings.audio,
                        video : opts.hasOwnProperty('videoSend') ? opts.videoSend : opt.settings.video,
                    };
                    if(opts.hasOwnProperty('bitrate')) message.bitrate = opts.bitrate;
                    // if(opts.display) message.display = JSON.stringify(opt.storage.display);
                    opt.server.SFU.send({
                        message : message,
                        jsep : jsep
                    });
                    if(callback && typeof callback === 'function') setTimeout(callback, 500)
                },
                error: function(error) {
                    Janus.error("WebRTC error:", error);
                    if(fail && typeof fail === 'function'){
                        setTimeout(fail, 500)
                        return;
                    }
                    if(opt.settings.video && opt.settings.audio){
                        opt.settings.video = false;
                        opt.settings.mode = 'audio';
                        opt.settings._mode = 'audio';
                        methods.publishOwnFeed({
                            video : false
                        });
                    }
                    else{
                        opt.settings.audio = false;
                        opt.settings.mode = 'unpublished';
                        opt.settings._mode = 'unpublished';
                        Janus.error("WebRTC error, all failed");
                        opt.my_stream_ctnr.html(templates.observing()).show();
                        methods.toolbarState();
                        Messenger.alert().Alert({
                            toast : true,
                            theme : 'info',
                            title : 'Unable to load your media devices. Proceeding as an observer'
                        });
                    }
                }
            });
        },
        toggleVideo : function(){
            if(!opt.initialized || opt.settings.screen || opt.TUTORIAL || !opt.settings.video || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;
            let muted = opt.server.SFU.isVideoMuted();
            Janus.log((muted ? "Unmuting" : "Muting") + " video stream...");
            if(!muted){
                opt.settings.video_paused = true;
                opt.server.SFU.muteVideo();
                methods.publishOwnFeed({
                    removeVideo : true,
                    videoSend : false,
                    video : false
                });
            }
            else{
                opt.settings.video_paused = false;
                opt.server.SFU.unmuteVideo();
                methods.publishOwnFeed({
                    addVideo : true
                });
            }
            methods.toolbarState()
        },
        toggleMute : function () {
            if(!opt.initialized || opt.TUTORIAL || !opt.settings.audio || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;
            let muted = opt.server.SFU.isAudioMuted();
            Janus.log((muted ? "Unmuting" : "Muting") + " local stream...");
            if(muted){
                opt.server.SFU.unmuteAudio();
                opt.settings.muted = false;
            }
            else{
                opt.server.SFU.muteAudio();
                opt.settings.muted = true;
            }
            methods.toolbarState()
        },
        toggleShareScreen : function(){
            if(!opt.initialized || opt.TUTORIAL || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;
            if(!opt.settings.screen){
                let config = {
                    video : "screen",
                    screenshareFrameRate : 30,
                    bitrate : (opt.DEMO ? 600000 : 1024000),
                    mode : 'screen'
                };
                if(opt.settings.video_paused || !opt.settings.video){
                    Janus.log('Video was paused, adding video');
                    config.addVideo = true;
                    config.videoSend = true;
                }
                else{
                    Janus.log('Video existed, replacing instead');
                    config.replaceVideo = true;
                }
                methods.publishOwnFeed(config, methods.screenShareReady, methods.screenShareRemove)
            }
            else{
                methods.screenShareRemove()
            }
        },
        screenShareReady : function(){
            opt.settings.screen = true;
            methods.toolbarState();
            opt.storage.broadcast_stream.getVideoTracks()[0].onended = function(){
                if (opt.settings.screen) {
                    Janus.log('Screen stopped by track ending');
                    methods.toggleShareScreen()
                }
            }
            if(opt.server.call_socket){
                opt.server.call_socket.whisper('screen_share_started', {
                    name : Messenger.common().name,
                    owner_id : Messenger.common().id,
                    janus_id : opt.storage.my_janus_id
                });
            }
        },
        screenShareRemove : function(){
            opt.settings.screen = false;
            opt.settings.mode = 'default';
            if(opt.settings.video_paused || !opt.settings.video){
                Janus.log('Remove screen media');
                if(!opt.settings.video) opt.settings.mode = 'audio';
                methods.publishOwnFeed({
                    removeVideo : true,
                    videoSend : false,
                    video : false,
                    bitrate : opt.settings.bitrate
                })
            }
            else{
                Janus.log('Replace screen with webcam');
                methods.publishOwnFeed({
                    replaceVideo : true,
                    bitrate : opt.settings.bitrate
                })
            }
            if(opt.server.call_socket){
                opt.server.call_socket.whisper('screen_share_ended', {
                    name : Messenger.common().name,
                    owner_id : Messenger.common().id,
                    janus_id : opt.storage.my_janus_id
                });
            }
            methods.toolbarState();
        },
        unpublish : function(){
            if(!opt.initialized || opt.TUTORIAL || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;
            opt.settings._mode = opt.settings.mode;
            opt.settings.mode = 'unpublished';
            opt.settings.screen = false;
            opt.settings.video_paused = false;
            opt.settings.muted = false;
            methods.toolbarState();
            if(opt.server.SFU){
                opt.server.SFU.send({
                    message : {
                        request : 'unpublish'
                    },
                    success : methods.toolbarState
                });
            }
        },
        publish : function(){
            if(!opt.initialized || opt.TUTORIAL || opt.settings._mode === 'unpublished' || opt.settings.mode !== 'unpublished') return;
            opt.settings.mode = opt.settings._mode;
            methods.toolbarState();
            if(!opt.settings.video){
                Janus.log('Add audio only');
                methods.publishOwnFeed({
                    videoSend : false,
                    video : false
                })
            }
            else{
                Janus.log('Add audio and video');
                methods.publishOwnFeed()
            }
        },
        leaveRoom : function(){
            if(!opt.initialized || opt.TUTORIAL || opt.settings.mode === 'destroyed' || CallManager.state().call_type === 1) return;
            Config.destroy(false, function(){
                opt.my_stream_ctnr.html('<div class="h5 p-2 text-danger">You left the streaming room. Use the settings toggle above to re-join</div>').show();
            });
        },
        joinRoom : function(){
            if(!opt.initialized || opt.TUTORIAL || opt.settings.mode !== 'destroyed' || CallManager.state().call_type === 1) return;
            Config.setup()
        },
        locateStorageItem : function(type, id){
            let collection, term,
                item = {
                    found : false,
                    index : 0
                };
            switch(type){
                case 'feed':
                    collection = opt.storage.feeds;
                    term = 'feed_id';
                break;
                case 'participant':
                    collection = opt.storage.participants;
                    term = 'id';
                break;
            }
            for(let i = 0; i < collection.length; i++) {
                if (collection[i][term] === id) {
                    item.found = true;
                    item.index = i;
                    break;
                }
            }
            return item
        },
        hangUp : function(end){
            Messenger.button().addLoader({id : (end ? '#end_call_btn' : '#hang_up_btn')});
            Config.destroy();
            end ? CallManager.endCall() : CallManager.leave(false)
        },
        maximizePublisher : function(publisher){
            Messenger.alert().Modal({
                wait_for_others : true,
                size : 'sm',
                icon : 'chalkboard-teacher',
                unlock_buttons : false,
                backdrop_ctrl : false,
                centered : true,
                title: 'Maximize Stream?',
                theme: 'info',
                body : publisher.name + ' is sharing their screen. Would you like to maximize their stream?',
                cb_btn_txt: 'Maximize',
                cb_btn_icon: 'chalkboard-teacher',
                cb_btn_theme: 'success',
                callback: function () {
                    try{
                        methods.requestFullScreen(publisher.janus_id)
                    }catch (e) {
                        console.log(e)
                    }
                },
                cb_close : true,
                timer : 15000
            });
            NotifyManager.sound('notify')
        },
        toolbarState : function(force){
            let rtc_opt = $(".rtc_nav_opt"), rtc_vid = $(".rtc_nav_video"), rtc_audio = $(".rtc_nav_audio"), rtc_screen = $(".rtc_nav_screen"),
                rtc_vid_on = $(".rtc_video_on"), rtc_vid_off = $(".rtc_video_off"), rtc_audio_on = $(".rtc_audio_on"), rtc_audio_off = $(".rtc_audio_off"),
                rtc_screen_on = $(".rtc_screen_on"), rtc_screen_off = $(".rtc_screen_off"), rtc_options_dropdown = $("#rtc_options_dropdown");
            rtc_opt.hide();
            rtc_options_dropdown.html('');
            if(!opt.initialized) return;
            switch(opt.settings.mode){
                case 'default':
                    if(opt.settings.video_paused){
                        rtc_vid.show();
                        rtc_vid_off.show();
                        rtc_audio.show();
                        opt.settings.muted ? rtc_audio_off.show() : rtc_audio_on.show();
                        rtc_screen.show();
                        rtc_screen_off.show();
                    }
                    else if(opt.settings.video && opt.settings.muted){
                        rtc_vid.show();
                        rtc_vid_on.show();
                        rtc_audio.show();
                        rtc_audio_off.show();
                        rtc_screen.show();
                        rtc_screen_off.show();
                    }
                    else if(opt.settings.video && opt.settings.audio){
                        rtc_vid.show();
                        rtc_vid_on.show();
                        rtc_audio.show();
                        rtc_audio_on.show();
                        rtc_screen.show();
                        rtc_screen_off.show();
                    }
                break;
                case 'video':
                    if(opt.settings.video_paused){
                        rtc_vid.show();
                        rtc_vid_off.show();
                        rtc_screen.show();
                        rtc_screen_off.show();
                    }
                    else{
                        rtc_vid.show();
                        rtc_vid_on.show();
                        rtc_screen.show();
                        rtc_screen_off.show();
                    }
                break;
                case 'audio':
                    if(opt.settings.muted){
                        rtc_audio.show();
                        rtc_audio_off.show();
                        rtc_screen.show();
                        rtc_screen_off.show();
                    }
                    else{
                        rtc_audio.show();
                        rtc_audio_on.show();
                        rtc_screen.show();
                        rtc_screen_off.show();
                    }
                break;
                case 'screen':
                    rtc_screen.show();
                    rtc_screen_on.show();
                    if(opt.settings.audio){
                        rtc_audio.show();
                        opt.settings.muted ? rtc_audio_off.show() : rtc_audio_on.show()
                    }
                break;
                case 'unpublished':

                break;
                case 'destroyed':

                break;
            }
            rtc_options_dropdown.html(templates.wb_room_dropdown());
            PageListeners.listen().tooltips()
        },
        tutorialMode : function (power) {
            opt.TUTORIAL = power;
            methods.toolbarState(true);
        },
        manageStreamPlayIcon : function(id, power){
            let play_btn = $("#publisher_play_" + id);
            power ? play_btn.show() : play_btn.hide()
        },
        requestFullScreen : function(id){
            let stream = document.getElementById('other_stream_src_' + id),
            maxStream = function(){
                if (stream.requestFullscreen) {
                    stream.requestFullscreen();
                } else if (stream.mozRequestFullScreen) { /* Firefox */
                    stream.mozRequestFullScreen();
                } else if (stream.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
                    stream.webkitRequestFullscreen();
                } else if (stream.msRequestFullscreen) { /* IE/Edge */
                    stream.msRequestFullscreen();
                }
            };
            if(stream){
                maxStream()
            }
        },
        toggleStreamMute : function(id, check){
            let stream = document.getElementById('other_stream_src_' + id),
                toggle = $('#publisher_sound_toggle_' + id),
            volume_on = '<i class="fas fa-volume-up"></i>', volume_off = '<i class="fas fa-volume-mute"></i>';
            if(stream){
                if(check){
                    toggle.html(stream.muted ? volume_off : volume_on);
                    return;
                }
                if(stream.muted){
                    stream.muted = false;
                    toggle.html(volume_on);
                }
                else{
                    stream.muted = true;
                    toggle.html(volume_off);
                }
            }
        },
        playStream : function (id) {
            let stream = document.getElementById('other_stream_src_' + id);
            if(stream) stream.play();
        }
    },
    templates = {
        wb_room_dropdown : function(){
            let unpublish = '<a class="dropdown-item" onclick="JanusServer.unpublish(); return false;" href="#"><i class="fas fa-stop"></i> Unpublish Stream</a>',
                publish = '<a class="dropdown-item" onclick="JanusServer.publish(); return false;" href="#"><i class="fas fa-play"></i> Publish Stream</a>',
                leave = '<a class="dropdown-item" onclick="JanusServer.leave(); return false;" href="#"><i class="fas fa-sign-out-alt"></i> Leave Room</a>',
                join = '<a class="dropdown-item" onclick="JanusServer.join(); return false;" href="#"><i class="fas fa-sign-in-alt"></i> Join Room</a>';
            switch (opt.settings.mode) {
                case 'destroyed': return join;
                case 'unpublished' :
                    if(opt.settings._mode === 'unpublished') return leave;
                    return publish + leave;
                default:
                    if(CallManager.state().call_type === 1) return unpublish;
                    return unpublish + leave
            }
        },
        my_video_stream : function () {
            return '<div class="shadow-sm rounded w-100 mx-auto embed-responsive embed-responsive-16by9">' +
                '<video style="background: url(\''+Messenger.common().avatar_md+'\') no-repeat 50% 50%; background-size: contain;" id="my_stream_src" muted autoplay playsinline class="embed-responsive-item"></video>' +
                '</div>'
        },
        observing : function(){
            return '<div class="shadow-sm rounded w-100 mx-auto embed-responsive embed-responsive-16by9">' +
                '<div id="my_stream_src" style="background: url(\''+Messenger.common().avatar_md+'\') no-repeat 50% 50%; background-size: contain;"  class="embed-responsive-item" /></div>' +
                '</div>'
        },
        other_video_stream : function (user) {
            return '<div class="col-12 '+(CallManager.state().thread_type === 2 ? 'col-md-6 col-lg-4' : '')+' mt-2 mb-4 other_stream_ctnr" id="other_stream_ctnr_'+user.feed_id+'">' +
                '<div class="col-12 text-center h4"><span class="badge badge-pill badge-light shadow">'+user.feed_name+'</span></div>'+
                '<div class="group_stream w-100 mx-auto embed-responsive embed-responsive-16by9">' +
                '<video style="background: url(\''+user.feed_avatar+'\') no-repeat 50% 50%; background-size: contain;" id="other_stream_src_'+user.feed_id+'" autoplay playsinline class="other-janus-stream embed-responsive-item"></video><span class="player_main_controls">' +
                templates.control_buttons(user.feed_id, true) +
                '</span>'+templates.play_button(user.feed_id, true)+'</div>'+
                '</div>';
        },
        observer : function(user){
            return '<div class="col-12 '+(CallManager.state().thread_type === 2 ? 'col-md-6 col-lg-4' : '')+' mt-2 mb-4 other_stream_ctnr observer-'+user.id+'" id="other_stream_ctnr_'+user.id+'">' +
                '<div class="col-12 text-center h4"><span class="badge badge-pill badge-light shadow">'+user.name+' - Watching</span></div>'+
                '<div class="group_stream w-100 mx-auto embed-responsive embed-responsive-16by9">' +
                '<div style="background: url(\''+user.avatar+'\') no-repeat 50% 50%; background-size: contain;"  class="embed-responsive-item" /></div></div>'+
                '</div>';
        },
        control_buttons : function(id, large){
            return '<span class="player_control_expand"><button title="Full Screen" type="button" onclick="JanusServer.fullScreen(\''+id+'\')" class="btn '+(large ? 'btn-lg' : 'btn-sm')+' text-white bg-dark"><i class="fas fa-expand"></i></button></span>' +
                '<span class="player_control_sound"><button title="Mute/Unmute" id="publisher_sound_toggle_'+id+'" type="button" onclick="JanusServer.toggleStreamMute(\''+id+'\')" class="btn '+(large ? 'btn-lg' : 'btn-sm')+' text-white bg-dark"><i class="fas fa-volume-up"></i></button></span>'
        },
        play_button : function (id, large) {
            return '<span id="publisher_play_'+id+'" class="player_control_play'+(large ? '_large' : '')+' NS"><button title="Play" type="button" onclick="JanusServer.playStream(\''+id+'\')" class="btn btn-circle btn-circle-'+(large ? 'xl' : 'lg')+' text-white bg-success glowing_warning_btn"><i class="fas fa-play-circle fa-3x"></i></button></span>'
        },
        loading_media : function () {
            return '<div class="col-12 text-center mt-2">\n' +
                '   <span class="h4">\n' +
                '     <span class="badge badge-pill badge-light">Loading media <span class="spinner-border spinner-border-sm text-primary" role="status"></span></span>\n' +
                '     </span>\n' +
                ' </div>'
        }
    };
    return {
        config : function () {
            return Config
        },
        hangUp : methods.hangUp,
        toggleScreenShare : methods.toggleShareScreen,
        toggleMute : methods.toggleMute,
        toggleVideo : methods.toggleVideo,
        unpublish : methods.unpublish,
        publish : methods.publish,
        join : methods.joinRoom,
        leave : methods.leaveRoom,
        toolbar : methods.toolbarState,
        tutorial : methods.tutorialMode,
        fullScreen : methods.requestFullScreen,
        toggleStreamMute : methods.toggleStreamMute,
        playStream : methods.playStream,
        socket : function () {
            return {
                onDisconnect : Sockets.disconnected,
                onReconnect : Sockets.reconnected,
                peerJoin : Sockets.joining,
                peerLeave : Sockets.leaving
            }
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());
