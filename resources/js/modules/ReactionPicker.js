window.ReactionPicker = (function () {
    var opt = {
        lock : true,
        using : false,
        elements : {

        },
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
        },
        ready : function(){
            console.log('ready');
        }
    },
    methods = {
        show : function(messageId){
            let message = $('#message_'+messageId);
            message.append('<input type="hidden" id="react-picker" style="display: none"/>');
            let picker = $('#react-picker');
        }
    },
    templates = {

    };
    return {
        init : mounted.Initialize,
        ready : mounted.ready,
        show : methods.show,
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());