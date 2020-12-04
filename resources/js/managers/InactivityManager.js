window.InactivityManager = (function () {
    var presence_chat = null,
        private_chat = null,
    methods = {
        resetTimer : function(arg){
            switch(arg.type){
                case 1:
                    if(!private_chat){
                        arg.activate();
                    }
                    else{
                        window.clearTimeout(private_chat);
                    }
                break;
                case 2:
                    if(!presence_chat){
                        arg.activate()
                    }
                    else{
                        window.clearTimeout(presence_chat)
                    }
                break;
            }
            methods.startTimer(arg);
        },
        startTimer : function(arg){
            switch(arg.type){
                case 1:
                    private_chat = setTimeout(function(){
                        methods.runInactive(arg)
                    }, 10800000); //3 hours (10800000)
                break;
                case 2:
                    presence_chat = setTimeout(function(){
                        methods.runInactive(arg)
                    }, (CallManager.state().initialized ? 1800000 : 360000)); //6 minutes (360000) - 30 minutes if in call (1800000)
                break;
            }
        },
        runInactive : function(arg){
            switch(arg.type){
                case 1:
                    private_chat = null;
                break;
                case 2:
                    presence_chat = null;
                break;
            }
            arg.inactive();
        },
        setupTimer : function(arg){
            $(window).mousemove(function(){
                methods.resetTimer(arg);
            });
            $(window).keypress(function(){
                methods.resetTimer(arg)
            });
            $(window).on("touchmove", function(){
                methods.resetTimer(arg)
            });
            methods.startTimer(arg)
        }
    };
    return {
        setup : methods.setupTimer
    };
}());