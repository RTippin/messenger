window.ThreadBots = (function () {
    var opt = {
        lock : true,
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
        }
    },
    methods = {

    },
    templates = {
        body : function(){
            return '';
        }
    };
    return {
        init : mounted.Initialize,
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());