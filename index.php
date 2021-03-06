<!DOCTYPE html>
<html>
<head>
    <title>bird</title>
    <style type="text/css">
        @font-face {
            font-family: 'Flappy Bird';
            src: url('fonts/FB.eot');
            src: url('fonts/FB.eot?#iefix') format('embedded-opentype'),
                 url('fonts/FB.woff') format('woff'),
                 url('fonts/FB.ttf') format('truetype'),
                 url('fonts/FB.svg#04b_19regular') format('svg');
            font-weight: normal;
            font-style: normal;
        }
        body, html {
            margin: 0px;
            padding: 0px;
            text-align: center;
            height: 100%;
        }
        canvas {
            margin-bottom: -4px;
            height: 100%;
        }
    </style>
    <script src="createjs-2015.11.26.min.js"></script>
    <script src="ndgmr.Collision.js?v1"></script>
    <script type="text/javascript">
    var stage, messageField, preload, canvas, floorImage, manifest, background, width, height, imStupidBird, topPipe, bottomPipe;
    var ground;
    var stageName = 'bg';
    var dead = false;
    var startJump = false;
    var keepJump = false;
    var jumpTime = 330;
    var pipeDelay = 50;
    var groundImg, pipes;
    var needCreate = true;
    var isCreateOne = false;
    var KEYCODE_SPACE = 32;

    document.onkeydown = handleKeyDown;

    function init()
    {
        //canvas
        canvas = document.getElementById(stageName);
        //create stage
        stage = new createjs.Stage(stageName);
        //setting message
        settingMessageField();
        // grab canvas width and height for later calculations:
        width = stage.canvas.width;
        height = stage.canvas.height;
        //images
        manifest = [
            {src:"img/bird.png", id:"bird"},
            {src:"img/background.png", id:"background"},
            {src:"img/ground.png", id:"ground"},
            {src:"img/pipe.png", id:"pipe"},
            {src:"img/restart.png", id:"restart"},
            {src:"img/start.png", id:"start"},
            {src:"img/share.png", id:"share"}
        ];

        preload = new createjs.LoadQueue();
        //events
        preload.addEventListener("progress", updateLoading);
        preload.addEventListener("complete", doneLoading);
        //setting Add files you want to load
        preload.loadManifest(manifest);
    }

    function updateLoading()
    {
        //preload.progress = 0~100%
        messageField.text = "Loading " + (preload.progress * 100) + "%";
        stage.update();
    }

    function doneLoading()
    {
        stage.removeChild(messageField);
        //load img
        var start = new createjs.Bitmap(preload.getResult("start"));
        start.x = width / 2 - (start.image.width) / 2;
        start.y = height / 2 - (start.image.height) / 2;

        stage.addChild(start);
        stage.update();

        start.addEventListener("click", handleStart);
    }

    function handleStart()
    {
        console.log('handleStart');
        //remove text
        stage.removeAllChildren();
        //Layer 1
        background = new createjs.Shape();
        //fill in img background drawRect(x, y, w, h)
        background.graphics.beginBitmapFill(preload.getResult('background')).drawRect(0,0,width,height);

        stage.addChild(background);

        //Layer 2
        pipes = new createjs.Container();
        stage.addChild(pipes)
        //groud create Layer 3
        groundImg = preload.getResult('ground');
        ground = new createjs.Shape();
        ground.graphics.beginBitmapFill(groundImg).drawRect(0, 0, width, groundImg.height);
        ground.y = height - groundImg.height;

        //groud2 create
        ground2 = new createjs.Shape();
        ground2.graphics.beginBitmapFill(groundImg).drawRect(0, 0, width, groundImg.height);
        ground2.y = height - groundImg.height;
        ground2.x = ground.x + width;

        stage.addChild(ground, ground2);

        //stupid bird (SpriteSheet = play image)
        var stupidBird = new createjs.SpriteSheet({
            'images': [preload.getResult('bird')],
            //set center and size of frames, center is important for later bird roation
            "frames": {"width": 92, "height": 64, "regX": 46, "regY": 32, "count": 3},
            // define two animations, run (loops, 0.21x speed) and dive (returns to dive and holds frame one static):
            "animations": {"fly": [0, 2, "fly", 0.21], "dive": [1, 1, "dive", 1]}
        });

        //bird is created (Sprite = show SpriteSheet)
        imStupidBird = new createjs.Sprite(stupidBird, "fly");

        var startX = width / 2 - 100;
        var startY = 512;

        imStupidBird.setTransform(startX, startY, 1, 1);
        // Set framerate
        imStupidBird.framerate = 30;

        // Use a tween to wiggle the bird up and down using a sineInOut Ease
        createjs.Tween.get(imStupidBird, {loop:true})
            .to( {y:startY + 100}, 380, createjs.Ease.sineInOut)
            .to( {y:startY}, 380, createjs.Ease.sineInOut);

        stage.addChild(imStupidBird);
        //jump
        stage.addEventListener("stagemousedown", handleJumpStart);
        //keepJump
        stage.addEventListener("stagemousedown", handleKeepJump);

        //tick
        createjs.Ticker.addEventListener("tick", tick);
    }

    function handleKeyDown(e)
    {
        switch(e.keyCode) {
            case KEYCODE_SPACE: handleJumpStart();
        }
    }

    function handleKeepJump(e)
    {
        keepJump = true;
    }

    function handleJumpStart(e)
    {
        if ( ! dead) {
            imStupidBird.gotoAndPlay('jump');
            startJump = true;
        }
    }

    function diveBird() {
        imStupidBird.gotoAndPlay("dive");
    }

    function createPipes()
    {
        //create one
        //top pipe
        topPipe = new createjs.Bitmap(preload.getResult('pipe'));
        topPipe.x = width + 100;
        topPipe.y = (ground.y - 250*2) * Math.random() + 250*1.5;
        pipes.addChild(topPipe);
        //bottom pipe
        bottomPipe = new createjs.Bitmap(preload.getResult('pipe'));
        bottomPipe.x = width + 236;
        bottomPipe.rotation = 180;
        bottomPipe.y = topPipe.y - 230;
        pipes.addChild(bottomPipe);
    }

    function tick(event)
    {
        var count = pipes.getNumChildren();

        if (ground2.x <= 0) {
            ground.x = 0;
            ground2.x = ground.x + width;
        } else {
            ground.x = ground.x - 10;
            ground2.x = ground2.x - 10;
        }
        //start
        if ( ! dead) {
            if (startJump) {

                if (pipeDelay == 0 && ! isCreateOne) {
                    //is create one
                    createPipes();
                    isCreateOne = true;
                } else {
                    pipeDelay = pipeDelay - 1;
                }

                if (isCreateOne) {
                    //1 group = 2(top and bottom)
                    for(var i = 0; i < count; i += 2) {
                        pipe = pipes.getChildAt(i);
                        pipeNext = pipes.getChildAt(i+1);

                        if (pipe) {

                            var collision = ndgmr.checkRectCollision(pipe, imStupidBird);

                            if (collision) {
                                if (collision.width > 8 && collision.height > 8) {
                                    console.log('die');
                                }
                            }
                            if (pipe.x <= -150) {
                                pipes.removeChild(pipe);
                                pipes.removeChild(pipeNext);
                            }

                            pipe.x = pipe.x - 10;
                            pipeNext.x = pipeNext.x - 10;

                            if (pipe.x <= 300 && ! pipe.isCreate) {
                                pipe.isCreate = true;
                                createPipes();
                            }
                        }
                    }
                }


                if (keepJump) {
                    createjs.Tween.removeTweens ( imStupidBird );

                    if (imStupidBird.y < -200) {
                        imStupidBird.y = -200;
                    }

                    createjs
                        .Tween
                        .get(imStupidBird)
                        //rotate to jump position and jump imStupidBird
                        .to({y:imStupidBird.y - 120, rotation: -20}, jumpTime, createjs.Ease.quadOut)
                        //reverse jump for smooth arch
                        .to({y:imStupidBird.y}, jumpTime, createjs.Ease.quadIn)
                        //rotate back
                        .to({y:imStupidBird.y + 200, rotation: 90}, 200, createjs.Ease.linear)
                        // change imStupidBird to diving position
                        .call(diveBird)
                        //drop to the bedrock
                        .to({y:ground.y - 30}, (height - (imStupidBird.y+200))/1.5, createjs.Ease.linear);
                }

                keepJump = false;
            }
        }
        stage.update();
    }

    function settingMessageField()
    {
        messageField = new createjs.Text("Loading", "24px Arial", "#333");
        messageField.maxWidth = 1000;
        messageField.textAlign = 'center';
        messageField.x = canvas.width / 2;
        messageField.y = canvas.height / 2;
        stage.addChild(messageField);
        stage.update();
    }
    </script>
</head>
<body onload="init();">
    <canvas id="bg" width="768" height="1024"></canvas>
</body>
</html>