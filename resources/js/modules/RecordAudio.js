import AudioRecorder from 'audio-recorder-polyfill';
import mpegEncoder from 'audio-recorder-polyfill/mpeg-encoder';
AudioRecorder.encoder = mpegEncoder;
AudioRecorder.prototype.mimeType = 'audio/mpeg';
window.MediaRecorder = AudioRecorder;

window.RecordAudio = (function () {
    var opt = {
        lock : true,
        using : false,
        elements : {
            visualizer : null,
            visualizerCtx : null,
            record_audio_action : null,
            start_audio_record : null,
            is_audio_recording : null,
            record_audio_results : null,
            completed_audio : null,
        },
        audio : {
            stream : null,
            ctx : null,
            animator : null,
            recorder : null,
            final_blob : null,
        },
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
        }
    },
    methods = {
        open : function(){
            if(opt.using) return;
            opt.using = true;
            Messenger.alert().Modal({
                size : 'md',
                backdrop_ctrl : false,
                overflow : true,
                theme : 'dark',
                icon : 'music',
                title: 'Record Audio Message',
                pre_loader: true,
                h4: false,
                onReady: methods.loadAudio,
                onClosed : methods.closed
            })
        },
        loadAudio : function(){
            if (navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({audio: true}).then(methods.ready, methods.error);
            } else {
                methods.error();
            }
        },
        ready : function(stream){
            Messenger.alert().fillModal({body : templates.body()});
            opt.elements.record_audio_action = $("#record_audio_action");
            opt.elements.start_audio_record = $("#start_audio_record");
            opt.elements.is_audio_recording = $("#is_audio_recording");
            opt.elements.record_audio_results = $("#record_audio_results");
            opt.elements.completed_audio = document.getElementById('completed_audio');
            opt.elements.visualizer = document.querySelector('.visualizer');
            opt.elements.visualizerCtx = opt.elements.visualizer.getContext("2d");
            opt.elements.visualizer.width = opt.elements.visualizer.parentElement.offsetWidth-10;
            opt.audio.stream = stream;
            opt.audio.recorder = new MediaRecorder(opt.audio.stream);
            opt.audio.recorder.addEventListener('dataavailable', methods.stopped)
            methods.visualize(opt.audio.stream);
        },
        start : function(){
            if(!opt.using) return;
            opt.elements.start_audio_record.hide();
            opt.elements.is_audio_recording.show();
            opt.audio.recorder.start();
        },
        stop : function(){
            if(!opt.using) return;
            opt.audio.recorder.stop();
            cancelAnimationFrame(opt.audio.animator);
            opt.elements.record_audio_action.hide();
            opt.elements.start_audio_record.show();
            opt.elements.is_audio_recording.hide();
            opt.elements.record_audio_results.show();
        },
        retry : function(){
            opt.audio.final_blob = null;
            opt.elements.completed_audio.src = '';
            opt.elements.record_audio_action.show();
            opt.elements.record_audio_results.hide();
            methods.visualize(opt.audio.stream);
        },
        send : function(){
            ThreadManager.Import().audioMessage(opt.audio.final_blob);
            methods.closed();
            Messenger.alert().destroyModal();
        },
        stopped : function(e){
            opt.audio.final_blob = e.data;
            opt.elements.completed_audio.src = window.URL.createObjectURL(opt.audio.final_blob);
        },
        visualize : function(stream){
            if(!opt.audio.ctx) {
                opt.audio.ctx = new AudioContext();
            }
            const source = opt.audio.ctx.createMediaStreamSource(stream);
            const analyser = opt.audio.ctx.createAnalyser();
            analyser.fftSize = 2048;
            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            source.connect(analyser);
            let draw = function() {
                const WIDTH = opt.elements.visualizer.width
                const HEIGHT = opt.elements.visualizer.height;
                analyser.getByteTimeDomainData(dataArray);
                opt.elements.visualizerCtx.fillStyle = 'rgb(48, 48, 48)';
                opt.elements.visualizerCtx.fillRect(0, 0, WIDTH, HEIGHT);
                opt.elements.visualizerCtx.lineWidth = 2;
                opt.elements.visualizerCtx.strokeStyle = 'rgb(0, 188, 40)';
                opt.elements.visualizerCtx.beginPath();
                let sliceWidth = WIDTH * 1.0 / bufferLength;
                let x = 0;
                for(let i = 0; i < bufferLength; i++) {
                    let v = dataArray[i] / 128.0;
                    let y = v * HEIGHT/2;
                    if(i === 0) {
                        opt.elements.visualizerCtx.moveTo(x, y);
                    } else {
                        opt.elements.visualizerCtx.lineTo(x, y);
                    }
                    x += sliceWidth;
                }
                opt.elements.visualizerCtx.lineTo(opt.elements.visualizer.width, opt.elements.visualizer.height/2);
                opt.elements.visualizerCtx.stroke();
                opt.audio.animator = requestAnimationFrame(draw);
            }
            opt.audio.animator = requestAnimationFrame(draw);
        },
        closed : function(){
            if(opt.audio.stream){
                opt.audio.stream.getTracks().forEach((track) => track.stop());
            }
            if(opt.audio.animator){
                cancelAnimationFrame(opt.audio.animator);
            }
            opt = {
                lock : false,
                using : false,
                elements : {
                    visualizer : null,
                    animator : null,
                    visualizerCtx : null,
                    record_audio_action : null,
                    start_audio_record : null,
                    is_audio_recording : null,
                    record_audio_results : null,
                    completed_audio : null,
                },
                audio : {
                    stream : null,
                    ctx : null,
                    recorder : null,
                    final_blob : null,
                },
            };
        },
        error : function(){
            Messenger.alert().destroyModal();
            Messenger.alert().Alert({
                theme : 'error',
                title : 'Unable to load your audio device.',
                toast : true
            });
            methods.closed();
        }
    },
    templates = {
        body : function(){
            return '<div id="record_audio_action" class="col-12 text-center">' +
                '<div class="col-12"><canvas class="visualizer" height="60px"></canvas></div>' +
                '<div id="start_audio_record"><h3>Start Recording</h3><button onclick="RecordAudio.start()" type="button" title="Start recording!" class="mx-3 mb-4 btn btn-circle btn-circle-xl btn-success"><i class="fas fa-microphone fa-2x"></i></button></div>' +
                '<div id="is_audio_recording" style="display: none"><h3>Recording...</h3><button onclick="RecordAudio.stop()" type="button" title="Finish recording!" class="mx-3 mb-4 btn btn-circle btn-circle-xl btn-danger glowing_warning_btn"><i class="fas fa-microphone-alt fa-2x"></i></button></div>' +
                '</div>' +
                '<div id="record_audio_results" class="col-12 text-center" style="display: none">' +
                '<audio id="completed_audio" controls></audio><hr>' +
                '<button onclick="RecordAudio.retry()" type="button" class="btn btn-lg btn-primary mr-2"><i class="fas fa-redo"></i> Retry</button>' +
                '<button onclick="RecordAudio.send()" type="button" class="btn btn-lg btn-success"><i class="fas fa-play-circle"></i> Send</button>'+
                '</div>';
        }
    };
    return {
        init : mounted.Initialize,
        open : methods.open,
        start : methods.start,
        stop : methods.stop,
        retry : methods.retry,
        send: methods.send,
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());