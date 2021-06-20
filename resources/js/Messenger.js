window.Messenger = (function () {
    var opt = {
        initialized : false,
        APP_NAME : 'Messenger',
        API : null,
        WEB : null,
        SOCKET : null,
        env : 'production',
        websockets : true,
        lockout : false,
        model : 'guest',
        auth : false,
        id : ''+(Math.floor(Math.random() * 90000) + 10000)+'',
        name : 'Guest User',
        slug : '#',
        avatar_md : null,
        mobile : false,
        teapot : 0,
        modal_close : null,
        dark_mode : true,
        css : {
            base : null,
            dark : null
        },
        csrf_token : document.querySelector('meta[name=csrf-token]').content,
        modules : [],
        modal_queue : []
    },
    methods = {
        Initialize : function(arg, environment){
            if(opt.initialized) return;
            opt.initialized = true;
            if("provider" in arg){
                opt.model = arg.provider.model;
                opt.auth = true;
                opt.id = arg.provider.id;
                opt.name = arg.provider.name;
                opt.slug = arg.provider.slug;
                opt.avatar_md = arg.provider.avatar_md;
            }
            if("common" in arg){
                opt.API = arg.common.api_endpoint + '/';
                opt.WEB = arg.common.web_endpoint;
                opt.SOCKET = arg.common.socket_endpoint;
                opt.APP_NAME = arg.common.app_name;
                opt.mobile = arg.common.mobile;
                opt.dark_mode = arg.common.dark_mode;
                if('base_css' in arg.common) opt.css.base = arg.common.base_css;
                if('dark_css' in arg.common) opt.css.dark = arg.common.dark_css;
                if('websockets' in arg.common) opt.websockets = arg.common.websockets;
            }
            if("call" in arg) CallManager.init(arg.call);
            if(environment) opt.env = environment;
            PageListeners.init();
            for(let key in arg.load){
                //We use the manager name to xhr load in the js
                //If loaded, we init and add to modules
                if (!arg.load.hasOwnProperty(key)) continue;
                methods.loadModule(key, arg.load[key])
            }
            for(let key in arg.modules){
                if (!arg.modules.hasOwnProperty(key)) continue;
                methods.loadModule(key, arg.modules[key])
            }
            PageListeners.listen().tooltips()
        },
        loadModule : function(key, obj){
            try{
                if(typeof window[key] !== 'undefined'){
                    methods.initModule({
                        name : key,
                        options : obj
                    })
                }
                else{
                    XHR.script({
                        file : obj.src,
                        name : key,
                        options : obj,
                        success : methods.initModule
                    })
                }
            }catch (e) {
                console.log(e)
            }
        },
        initModule : function(js){
            try{
                if(!opt.modules.includes(js.name)) opt.modules.push(js.name);
                if(typeof window[js.name] !== 'undefined' && typeof window[js.name]['init'] !== 'undefined') window[js.name].init(js.options)
            }catch (e) {
                console.log(e)
            }
        },
        LockSmith : function(){
            opt.teapot = 0;
            opt.modules.forEach(function(name){
                if(typeof window[name] !== 'undefined' && typeof window[name]['lock'] !== 'undefined') window[name].lock(false);
            });
        },
        addScripts : function(jsFile){
            let s = document.createElement('script');
            s.type = 'text/javascript';
            s.appendChild(document.createTextNode(jsFile.data));
            document.body.appendChild(s);
        },
        checkCsrfToken : function(token){
            if(opt.csrf_token !== token){
                opt.csrf_token = token;
                window.Laravel = { csrfToken: token };
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                document.querySelector('meta[name=csrf-token]').content = token
            }
        }
    },
    format = {
        makeUtcLocal : function(date){
            return moment.utc(date).local().format('YYYY-MM-DD HH:mm:ss')
        },
        makeTimeAgo : function(date){
            return moment(format.makeUtcLocal(date)).fromNow()
        },
        escapeHtml : function(text) {
            let map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; })
        },
        focusEnd : function (elm) {
            if(!elm) return;
            elm.focus();
            if(elm.value){
                elm.setSelectionRange(elm.value.length, elm.value.length)
            }
        },
        timeDiffInUnit : function (date1, date2, unit) {
            if(!date1 || !date2 || !unit) return 0;
            let d1 = moment(format.makeUtcLocal(date1)),
                d2 = moment(format.makeUtcLocal(date2));
            return d1.diff(d2, unit)
        },
        copyText : function(btnID, inputID){
            let input = document.getElementById(inputID), btn = $("#"+btnID);
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand("copy");
            btn.removeClass('btn-primary').addClass('btn-success').html('<i class="far fa-clipboard"></i> Copied');
            setTimeout(function () {
                btn.removeClass('btn-success').addClass('btn-primary').html('<i class="far fa-copy"></i> Copy');
            }, 2000)
        },
        shortcodeToImage : function(body){
            return typeof joypixels !== 'undefined'
                ? joypixels.toImage(body)
                : body;
        },
        shortcodeToUnicode : function(body){
            return typeof joypixels !== 'undefined'
                ? joypixels.shortnameToUnicode(body)
                : body;
        }
    },
    buttons = {
        addLoader : function(arg){
            let button = $(arg.id);
            if(!button.length) return;
            $(arg.id).append(' <i class="fas fa-sync-alt bLoading"></i>');
            $(arg.id).prop("disabled", true);
        },
        spinAction : function(elm, disabled){
            if(typeof elm === 'undefined' || elm === null) return;
            if(disabled){
                elm.prop('disabled', true);
                elm.find('i').addClass('spin-me-round');
            }
            else{
                elm.prop('disabled', false);
                elm.find('i').removeClass('spin-me-round');
            }
        },
        removeLoader : function(){
            $(".bLoading").remove();
            $(".btn").prop("disabled", false);
        }
    },
    alerts = {
        //Global modal module with default options you can override
        // Messenger.alerts().Modal({
        //     wait_for_others : false, //Default, set true to have modal wait to show until other modals close
        //     title : 'Alert',         //Default, set title of modal
        //     allow_close : true,      //Default, (false) removes btns, user can't close modal
        //     unlock_buttons : true    //Default, if true, removes all btn loaders and disabled states on page
        //     close_btn : true,        //Default, set false to hide bottom close button
        //     theme : 'info',          //Default, bootstrap theme prefix (danger, success, etc)
        //     icon : 'info-sign',      //Default, header Font Awesome icon, use (fas fa-) suffix
        //     size : 'md',             //Default, use bootstrap size prefix (xs, sm, md, ect)
        //     h4 : true,               //Default, makes text in body h4. Set false if importing custom html
        //     backdrop_ctrl : true,    //Default, set false to stop modal close on backdrop click
        //     overflow : false,        //Default, set true to allow inner modal to scroll
        //     close_btn_txt : 'Close', //Default, change close button text
        //     pre_loader : false,      //Default. If true, modal expects a call to the onReady() below (no body set here)
        //     centered : false,        //Default. If true, will center modal in middle of screen
        //     timer : false            //Default, set to int in milliseconds to auto close modal on timeout
        //     body : 'body content',   //Not set, define your modal content here. Set false to hide body completely
        //     callback : function(){   //Optional, requires cb_btn opts below
        //         //your executed callback logic
        //         //by default this will not close modal
        //     },
        //     cb_btn_theme : '',       //Required when using callback, set bootstrap theme prefix
        //     cb_btn_icon : '',        //Required when using callback, Font Awesome icon, use FA suffix
        //     cb_btn_txt : '',         //Required when using callback, set callback btn text
        //     cb_close : true,         //Optional when using callback, set true to make modal close same time when clicking callback
        //     onReady : function(){    //Optional. Execute code here once modal has opened
        //         //execute logic here on modal show
        //         //Required if using pre_loader. You must call in here
        //         //the fillModal({}) method to remove preload, fill body/title
        //         //Messenger.alerts().fillModal({body : html, title : optional}) Fills in modal
        //      },
        //      onClosed : function(){  //Optional. Run this to execute code once modal closes
        //          //console.log('Modal Closed!');
        //      }
        // });
        loader : function(grow){
            return '<div class="col-12 my-2 text-center"><div class="spinner-'+(grow ? 'grow' : 'border')+' text-primary" role="status"></div></div>'
        },
        destroyModal : function(){
            $(".modal-backdrop").remove();
            $(".modal").remove();
            $("body").removeClass('modal-open');
        },
        Modal : function(arg){
            let elm = {
                modal_backdrop : $(".modal-backdrop"),
                modal : $(".modal")
            },
            defaults = {
                title : 'Alert',
                allow_close : true,
                unlock_buttons : true,
                close_btn : true,
                theme : 'info',
                icon : 'info-circle',
                callback : null,
                size : 'md',
                h4 : true,
                backdrop_ctrl : true,
                overflow : false,
                close_btn_txt : 'Close',
                pre_loader : false,
                centered : false,
                timer : false
            };
            if("wait_for_others" in arg && elm.modal.length){
                opt.modal_queue.push(arg);
                return;
            }
            PageListeners.listen().disposeTooltips();
            if(elm.modal.length || elm.modal_backdrop.length){
                alerts.destroyModal()
            }
            let options = Object.assign({}, defaults, arg),
            bottom = function(options){
                if(!options.allow_close || !options.close_btn){
                    return "";
                }
                if(options.callback){
                    return "<div class='modal-footer'><div class='mx-auto'><button type='button' class='btn btn-md btn-light modal_close' data-dismiss='modal'>Cancel</button>" +
                        "<button id='modal_cb_btn' type='button' class='ml-2 btn btn-md btn-"+options.cb_btn_theme+" modal_callback "+(options.pre_loader ? "NS" : "")+"'><i class='fas fa-"+options.cb_btn_icon+"'></i> "+options.cb_btn_txt+"</button></div></div>";
                }
                return "<div class='modal-footer'><div class='mx-auto'><button type='button' class='btn btn-sm btn-light modal_close' data-dismiss='modal'>"+options.close_btn_txt+"</button></div></div>";
            },
            body = function(options){
                return (options.body || options.pre_loader ? "<div id='body_modal' class='modal-body text-dark "+(options.h4 ? ' h4' : '')+"'>"+(options.pre_loader ? alerts.loader() : options.body)+"</div>" : "");
            },
            template = function(options){
                return "<div id='main_modal' class='modal fade' role='dialog'>" +
                        "<div class='modal-dialog modal-"+options.size+(options.centered ? ' modal-dialog-centered' : '')+(options.overflow ? ' modal-dialog-scrollable' : '')+" ' role='document'>" +
                        "<div class='modal-content'>" +
                        "<div class='modal-header pb-2 text-"+(options.theme === 'warning' ? 'dark' : 'light')+" bg-gradient-"+options.theme+"'>" +
                        "<span class='h5'><i class='fas fa-"+options.icon+"'></i> <strong><span id='title_modal'>"+options.title+"</span></strong></span>" +
                        (options.allow_close ? "<button type='button' class='close modClose' data-dismiss='modal' aria-hidden='true'><i class='fas fa-times'></i></button>" : "" )+
                        "</div>"+body(options)+bottom(options)+"</div></div></div>";
            };
            $("body").append(template(options));
            $("#main_modal").modal({backdrop: (!options.allow_close || !options.backdrop_ctrl ? 'static' : true), keyboard: false})
            .on('shown.bs.modal', function () {
                if(options.timer){
                    opt.modal_close = setTimeout(function(){
                        $(".modal").modal("hide")
                    }, options.timer)
                }
            })
            .on('click', '.modal_callback', function() {
                if(options.callback){
                    buttons.addLoader({id : $(this)});
                    options.callback();
                    if('cb_close' in options) $(".modal").modal("hide");
                    if('onClosed' in options) options.onClosed();
                }
            })
            .on('hidden.bs.modal', function () {
                clearInterval(opt.modal_close);
                $(this).remove();
                if(options.unlock_buttons) buttons.removeLoader();
                if('onClosed' in options) options.onClosed();
                if(opt.modal_queue.length){
                    alerts.Modal(opt.modal_queue[0]);
                    opt.modal_queue.shift()
                }
            });
            if('onReady' in options) options.onReady()
        },
        fillModal : function(arg){
            $("#modal_cb_btn").show();
            $("#body_modal").html(("loader" in arg ? alerts.loader() : arg.body));
            if("title" in arg) $("#title_modal").html(arg.title);
            if("no_close" in arg) $(".modClose, .modal-footer").remove();
        },
        //Global alert popup
        // Messenger.alert().Alert({
        //     close : false,           //Default, set true to close all open alerts before showing the next including modals
        //     title : 'Alert',         //Default, set title of alert
        //     theme : 'success',       //Default, bootstrap theme prefix (danger, success, etc) / May use success, info, warning, or error is using toast
        //     icon : 'info-sign',      //Default, header Font Awesome icon, use FA suffix
        //     timer : 5000,            //Default, set time until auto close. Set false to not auto close
        //     body : 'body'            //Not set, define your alert content here
        //     toast : false            //If true, we use toastr instead of bootstrap alert and the added options below
        //     close_toast : false      //If true, we close other toast before showing this
        //     toast_options : {        //Default options for toastr, override globals here
        //         https://github.com/CodeSeven/toastr for docs
        //     }
        // });
        Alert : function(arg){
            let defaults = {
                close : false,
                title : 'Alert',
                theme : 'success',
                icon : 'info-circle',
                body : '',
                timer : 5000,
                toast : false,
                close_toast : false,
                toast_options : {}
            },
            options = Object.assign({}, defaults, arg),
            modal = $(".modal");
            if(options.toast){
                if(options.close){
                    modal.modal("hide");
                    $(".alert").remove();
                    buttons.removeLoader()
                }
                if(options.close_toast) toastr.remove();
                toastr[options.theme](options.body, options.title, options.toast_options);
                return;
            }
            buttons.removeLoader();
            modal.modal("hide");
            if(options.close) $(".alert").remove();
            let alert = $('<div onclick="$(this).remove()" role="alert" class="pointer_area alert alert-'+options.theme+' alert-dismissable NS fade show mb-2"><button data-dismiss="alert" type="button" class="close"><i class="fas fa-times"></i></button>' +
                '<strong><i class="fas fa-'+options.icon+'"></i> '+options.title+':</strong> '+options.body+'</div>');
            alert.prependTo("#alert_container");
            alert.css('opacity', '1').slideDown(300, function(){
                if(options.timer){
                    setTimeout( function () {
                        alert.remove()
                    }, options.timer);
                }
            });
        },
        showAvatar : function (name, avatar) {
            alerts.Modal({
                icon : 'image',
                theme : 'dark',
                title : name+'\'s Photo',
                pre_loader : true
            });
            let img = new Image();
            img.onload = function() {
                alerts.fillModal({
                    body : '<div class="text-center"><img src="'+this.src+'" class="img-fluid rounded" /></div>'
                });
            };
            img.onerror = function() {
                alerts.fillModal({
                    body : '<div class="text-center"><img src="/vendor/messenger/images/image404.png" class="img-fluid rounded" /></div>'
                });
            };
            img.src = avatar;
        }
    },
    XHR = {
        //Global post/request function using axios
        // Messenger.xhr().payload({
        //     route : '/post/here',            //(Required)Set the URI to post to
        //     data : {
        //          input : 'data'              //(Required)data is an object of all data to post to URI
        //     },
        //     exports : {                      //(Not Required)if set, it will send data there instead
        //          name : 'ManagerName',       //Manager to call by name string
        //          sub : 'SubFunctionName'     //Manager sub function to call by name string
        //     },
        //     shared : {                       //(Not Required)If set, on success this data will be merged with the
        //          arg : true,                 //received data from the backend
        //          more : 'stuff'
        //     },
        //     success : function(response){    //(Not required) On success, we pass data and run your calls inside success function
        //          console.log(response)
        //     },
        //     fail : function(error){          //(Not Required) if the post fails, it will by default pass the error msg to the handler popup
        //          console.log(error);         //If you set this function, it will instead pass you the error for you to handle
        //          doSomething();
        //     },
        //     bypass : true                    //(Not Required) - Set true if you wish to use your own fail method while continuing
        //                                      //to allow the handler to popup the error message
        //     fail_alert : true                //(Not Required) - Set true if you want error to be in alert and not modal
        //     close_modal : true               //(Not Required) - Set true if you wish close modal on success/fail
        //     lockout : true                   //(Not Required) - Set true to lockout all further post/gets when called
        // });
        payload : function(arg, method){
            if(opt.lockout) return;
            if("lockout" in arg) opt.lockout = true;
            axios[method ? method : 'post'](arg.route,arg.data)
            .then(function (response) {
                methods.LockSmith();
                PageListeners.listen().disposeTooltips();
                if('close_modal' in arg) alerts.destroyModal();
                if('exports' in arg){
                    window[arg.exports.name][arg.exports.sub](Object.assign(response.data, arg.exports));
                    return;
                }
                if('success' in arg && typeof arg.success === 'function'){
                    if('shared' in arg){
                        arg.success(Object.assign(response.data, arg.shared));
                        return;
                    }
                    arg.success(response.data);
                }
            })
            .catch(function (error) {
                if(opt.env === 'local'){
                    console.trace();
                    console.log(error.response)
                }
                try{
                    if(error && "response" in error && [418, 502, 504].includes(error.response.status)){
                        handle.fillTeapot('payload', arg);
                        return;
                    }
                }catch (e) {
                    console.log(e)
                }
                PageListeners.listen().disposeTooltips();
                methods.LockSmith();
                buttons.removeLoader();
                if('close_modal' in arg) alerts.destroyModal();
                if("lockout" in arg) opt.lockout = false;
                if('fail' in arg){
                    if(typeof arg.fail === 'function') arg.fail(error.response);
                    if(!('bypass' in arg)) return;
                }
                handle.xhrError({
                    type : ('fail_alert' in arg && arg.fail_alert ? 2 : 1),
                    response : error.response,
                    fail_keep_open : 'fail_keep_open' in arg
                });
            });
        },
        request : function(arg){
            if(opt.lockout) return;
            if("lockout" in arg) opt.lockout = true;
            axios.get(arg.route)
            .then(function (response) {
                PageListeners.listen().disposeTooltips();
                if('close_modal' in arg) alerts.destroyModal();
                methods.LockSmith();
                if('exports' in arg){
                    window[arg.exports.name][arg.exports.sub](Object.assign(response.data, arg.exports));
                    return;
                }
                if('success' in arg && typeof arg.success === 'function'){
                    if('shared' in arg){
                        arg.success(Object.assign(response.data, arg.shared));
                        return;
                    }
                    arg.success(response.data);
                }
            })
            .catch(function (error) {
                if(opt.env === 'local'){
                    console.trace();
                    console.log(error.response)
                }
                try{
                    if(error && "response" in error && [418, 502, 504].includes(error.response.status)){
                        handle.fillTeapot('request', arg);
                        return;
                    }
                }catch (e) {
                    console.log(e)
                }
                PageListeners.listen().disposeTooltips();
                methods.LockSmith();
                buttons.removeLoader();
                if('close_modal' in arg) alerts.destroyModal();
                if("lockout" in arg) opt.lockout = false;
                if('fail' in arg){
                    if(typeof arg.fail === 'function') arg.fail(error.response);
                    if(!('bypass' in arg)) return;
                }
                handle.xhrError({type : ('fail_alert' in arg && arg.fail_alert ? 2 : 1), response : error.response});
            });
        },
        script : function(arg){
            if(!opt.initialized) return;
            if(opt.lockout) return;
            if("lockout" in arg) opt.lockout = true;
            axios.get(arg.file)
            .then(function(response) {
                methods.addScripts(response);
                opt.modules.push(arg.name);
                if("success" in arg) arg.success(arg);
            })
            .catch(function(error) {
                if(opt.env === 'local'){
                    console.trace();
                    console.log(error.response)
                }
                console.log('Failed to load '+arg.file);
                if("fail" in arg) arg.fail();
            })
        },
        lockout : function (state) {
            opt.lockout = state
        }
    },
    handle = {
        fillTeapot : function(flavor, tea){
            if(opt.teapot > 4){
                handle.xhrError();
                return;
            }
            opt.teapot++;
            XHR[flavor](tea)
        },
        xhrError : function(arg){
            $('body').find(".btn").prop('disabled', false);
            let errMessages = function(){
                switch(Math.floor(Math.random() * Math.floor(3))){
                    case 0: return 'Your request has encountered an error. We have been made aware of this issue';
                    case 1: return 'It seems we are having trouble processing your request. Our team has been notified';
                    case 2: return 'Something went wrong. We are sorry about that, our team has been informed of the situation';
                }
            },
            errToast = function(body, close){
                alerts.Alert({
                    close_toast : close,
                    close : !arg.fail_keep_open,
                    toast : true,
                    theme : 'error',
                    title : body
                })
            },
            errModal = function(body){
                alerts.Modal({
                    theme : 'danger',
                    icon : 'times',
                    title : 'Error',
                    body : body
                })
            },
            oldFormError = function(arg){
                if(typeof arg.response.data.errors.forms === 'object'){
                    let theStack = '<ul class="'+(arg.type === 2 ? 'p-0 ml-3' : '')+'">';
                    $.each( arg.response.data.errors.forms, function( key, value ) {
                        $.each( value, function( key2, errm ) {
                            theStack += '<li>' + errm + '</li>';
                        });
                    });
                    theStack += '</ul>';
                    if(arg.type === 2){
                        errToast(theStack, !("no_close" in arg));
                        return;
                    }
                    errModal(theStack);
                    return;
                }
                if(arg.type === 2){
                    errToast(arg.response.data.errors.forms, true);
                    return;
                }
                errModal(arg.response.data.errors.forms);
            };
            buttons.removeLoader();
            if(!arg || arg && typeof arg.response === 'undefined'){
                errToast(errMessages(), true);
                return;
            }
            if(arg.response.status === 413){
                errToast('File upload too large', true);
                return;
            }
            if(arg.response.status === 500){
                errToast(errMessages(), true);
                return;
            }
            if(typeof arg.response.data !== 'undefined'
                && typeof arg.response.data.errors !== 'undefined'
                && typeof arg.response.data.errors.forms !== 'undefined'){
                return oldFormError(arg)
            }
            if(typeof arg.response.data === 'undefined' ||
                typeof arg.response.data.message === 'undefined'){
                errToast(errMessages(), true);
                return;
            }
            if(typeof arg.response.data.errors !== 'undefined'
                && typeof arg.response.data.errors === 'object'){
                let theStack = '<ul class="'+(arg.type === 2 ? 'p-0 ml-3' : '')+'">';
                for(let field in arg.response.data.errors) {
                    if (!arg.response.data.errors.hasOwnProperty(field)) continue;
                    arg.response.data.errors[field].forEach((error) => {
                        theStack += '<li>' + error + '</li>';
                    })
                }
                theStack += '</ul>';
                if(arg.type === 2){
                    errToast(theStack, !("no_close" in arg));
                    return;
                }
                errModal(theStack);
                return;
            }
            if(arg.type === 2){
                errToast(arg.response.data.message, true);
                return;
            }
            errModal(arg.response.data.message);
        },
        switchCss : function (dark){
            let og = document.getElementById('main_css'),
                head  = document.getElementsByTagName('head')[0],
                link  = document.createElement('link');
            link.rel  = 'stylesheet';
            link.href = (dark ? opt.css.dark : opt.css.base);
            opt.dark_mode = dark;
            head.prepend(link);
            link.onload = function () {
                og.remove();
                this.id = 'main_css'
            }
            if(opt.modules.includes('EmojiPicker')){
                EmojiPicker.updateThemes(dark);
            }
        }
    },
    forms = {
        updateSlug : function(slug){
            opt.slug = slug;
        },
        Logout : function(){
            if(opt.model === 'guest') return;
            Messenger.alert().Modal({
                size : 'sm',
                icon : 'sign-out-alt',
                pre_loader : true,
                centered : true,
                unlock_buttons : false,
                allow_close : false,
                backdrop_ctrl : false,
                title: 'Logging out',
                theme: 'primary'
            });
            if(opt.modules.includes('NotifyManager')) NotifyManager.sockets().disconnect();
            XHR.payload({
                route : '/logout',
                data : {},
                lockout : true,
                success : function () {
                    location.replace('/')
                },
                fail : function () {
                    location.reload()
                }
            })
        },
    };
    return {
        init : methods.Initialize,
        common : function(){
            return {
                APP_NAME : opt.APP_NAME,
                API : opt.API,
                WEB : opt.WEB,
                SOCKET : opt.SOCKET,
                model : opt.model,
                id : opt.id,
                name : opt.name,
                slug : opt.slug,
                avatar_md : opt.avatar_md,
                modules : opt.modules,
                mobile : opt.mobile,
                csrf_token: opt.csrf_token,
                websockets : opt.websockets,
                dark_mode : opt.dark_mode,
                env : opt.env
            };
        },
        xhr : function () {
            return XHR
        },
        handle : function(){
            return handle
        },
        button : function(){
            return buttons
        },
        alert : function(){
            return alerts
        },
        forms : function(){
            return forms
        },
        format : function(){
            return format
        },
        token : methods.checkCsrfToken
    };
}());