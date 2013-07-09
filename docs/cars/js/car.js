(function(){
	var mycar = $('#mycar');
	var cloud= $('#mycar-cloud');
	var width = mycar.width() || 76;
	var height = mycar.height() || 184;
	var way1 = buildTrace([[1072, 604], [1022, 488], [925, 442], [439, 440], [322, 528], [374, 766], [350, 906], [304, 1099], [361, 1273], [357, 1458], [361, 1586], [370, 1685], [297, 1892], [333, 2040], [372, 2183], [351, 2326], [376, 2503], [324, 2665], [306, 2819], [382, 3048], [352, 3167], [377, 3358], [300, 3590], [296, 3678], [367, 3821], [366, 3950], [378, 4213], [303, 4502], [375, 4726], [344, 4882], [369, 5080], [330, 5283]]);
	var way2 = buildTrace([[1539, 5160], [1629, 4805], [1548, 4437], [1634, 3940], [1561, 3615], [1627, 3098], [1554, 2742], [1629, 2233], [1548, 1869], [1632, 1358], [1552, 993], [1596, 550], [1520, 458], [1141, 466], [1072, 654]]);
	var delay = 0;
	
	mycar.show();
	
	mycar.click(function(){
		cloud.fadeIn();
		delay = 200;
	});
	
	var index = 2;
	var goal = way1[0][1];
	var fixed = 1;
	var way = way1;
	var direction = 1;
	
	point = way[index];
	
	mycar.css({
		left: point[0] - width/2+'px',
		top: point[1] - height/2+'px'
	});
	
	cloud.css({
		left: point[0] - width/2+50+'px',
		top: point[1] - height/2+'px'
	});
	
	
	setInterval(function(){
		if (delay > 0) {
			delay--;
			if (delay == 0)
				cloud.fadeOut();
		}
		var pos = $(window).scrollTop()+$(window).height()/2 + 80;
		
		if (index < 10 && $(window).scrollTop() < 50) return;
		if (index >= way.length) {
			fixed = 0;
			direction = -direction;
			way = direction == 1 ? way1 : way2;
			index = 2;
			goal = pos;
		}
		
		var point = way[index];
		
		if (!fixed) {
			if (direction == 1) {
				goal = Math.max(goal, pos);
				if (goal-pos > 300 || $(window).scrollTop()+$(window).height() >= $('body').height()) {
					fixed = 1;
					goal = 1000000;
				}
			} else {
				goal = Math.min(goal, pos);
				if (pos - goal > 300 || goal < 700) {
					fixed = 1;
					goal = 0;
				}
			}
		}

		if (Math.abs(goal - point[1]) < 20) {
			fixed = 0;
			return;
		}
		mycar.css({
			left: point[0] - width/2+'px',
			top: point[1] - height/2+'px'
		});
		cloud.css({
			left: point[0] - width/2+50+'px',
			top: point[1] - height/2+'px'
		});
		
		var a = Math.atan2(way[Math.max(0, index-5)][1] - way[index][1], way[Math.max(0, index-5)][0] - way[index][0]) * 180 / Math.PI;
		mycar.rotate(a-90);
		
		if (Math.abs(pos - point[1]) > $(window).height()/2+200) {
			index += 10;
		} else {
			index += 1;
		}
	}, 10);
	
	function buildTrace(points){
		var wrap = jQuery('.slave');
		var way = [];
		for (i in points) {
			points[i][0] -= 424;
			points[i][1] += 10;
			var x = points[i][0];
			var y = points[i][1];
			//wrap.append('<div class="__point" style="position: absolute; border-radius: 10px; left: '+(x-10)+'px; top: '+(y-10)+'px; width:20px; height: 20px; background: red; z-index: 100">'+i+'</div>');
		}

		var speed = 5;
		var vx = 0;
		var vy = -speed;
		var x = points[0][0];
		var y = points[0][1];
		var dt = 1;
		for (i = 1; i < points.length; i++) {
			var x1 = points[i][0];
			var y1 = points[i][1];
			var j = 0;
			while (Math.sqrt((x-x1)*(x-x1) + (y-y1)*(y-y1)) > 20) {
				j++;
				if (j > 200) 
					break;
				x += vx * dt;
				y += vy * dt;
				var v = Math.sqrt(vx*vx + vy*vy);
				var a = Math.atan2(vy, vx); //rad
				var a2 = Math.atan2(y1-y, x1-x); //rad
				if (a < 0) a += 2 * Math.PI;
				if (a2 < 0) a2 += 2 * Math.PI;
				da = a2 - a;
				if (da > Math.PI || da < -Math.PI)
					da = -da;

				var max_a = 0.05;
				da = da < 0 ? Math.max(-max_a, da) : Math.min(max_a, da);
				a += da;
				vx = speed * Math.cos(a);
				vy = speed * Math.sin(a);

				//wrap.append('<div class="__point" style="position: absolute; left: '+x+'px; top: '+y+'px; width:3px; height: 3px; background: blue; z-index: 100"></div>');
				way.push([x, y]);
			}
		}
		return way;
	}
})();


// VERSION: 2.2 LAST UPDATE: 13.03.2012
/* 
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * 
 * Made by Wilq32, wilq32@gmail.com, Wroclaw, Poland, 01.2009
 * Website: http://code.google.com/p/jqueryrotate/ 
 */

// Documentation removed from script file (was kinda useless and outdated)

(function($) {
var supportedCSS,styles=document.getElementsByTagName("head")[0].style,toCheck="transformProperty WebkitTransform OTransform msTransform MozTransform".split(" ");
for (var a=0;a<toCheck.length;a++) if (styles[toCheck[a]] !== undefined) supportedCSS = toCheck[a];
// Bad eval to preven google closure to remove it from code o_O
// After compresion replace it back to var IE = 'v' == '\v'
var IE = eval('"v"=="\v"');

jQuery.fn.extend({
    rotate:function(parameters)
    {
        if (this.length===0||typeof parameters=="undefined") return;
            if (typeof parameters=="number") parameters={angle:parameters};
        var returned=[];
        for (var i=0,i0=this.length;i<i0;i++)
            {
                var element=this.get(i);	
                if (!element.Wilq32 || !element.Wilq32.PhotoEffect) {

                    var paramClone = $.extend(true, {}, parameters); 
                    var newRotObject = new Wilq32.PhotoEffect(element,paramClone)._rootObj;

                    returned.push($(newRotObject));
                }
                else {
                    element.Wilq32.PhotoEffect._handleRotation(parameters);
                }
            }
            return returned;
    },
    getRotateAngle: function(){
        var ret = [];
        for (var i=0,i0=this.length;i<i0;i++)
            {
                var element=this.get(i);	
                if (element.Wilq32 && element.Wilq32.PhotoEffect) {
                    ret[i] = element.Wilq32.PhotoEffect._angle;
                }
            }
            return ret;
    },
    stopRotate: function(){
        for (var i=0,i0=this.length;i<i0;i++)
            {
                var element=this.get(i);	
                if (element.Wilq32 && element.Wilq32.PhotoEffect) {
                    clearTimeout(element.Wilq32.PhotoEffect._timer);
                }
            }
    }
});

// Library agnostic interface

Wilq32=window.Wilq32||{};
Wilq32.PhotoEffect=(function(){

	if (supportedCSS) {
		return function(img,parameters){
			img.Wilq32 = {
				PhotoEffect: this
			};
            
            this._img = this._rootObj = this._eventObj = img;
            this._handleRotation(parameters);
		}
	} else {
		return function(img,parameters) {
			// Make sure that class and id are also copied - just in case you would like to refeer to an newly created object
            this._img = img;

			this._rootObj=document.createElement('span');
			this._rootObj.style.display="inline-block";
			this._rootObj.Wilq32 = 
				{
					PhotoEffect: this
				};
			img.parentNode.insertBefore(this._rootObj,img);
			
			if (img.complete) {
				this._Loader(parameters);
			} else {
				var self=this;
				// TODO: Remove jQuery dependency
				jQuery(this._img).bind("load", function()
				{
					self._Loader(parameters);
				});
			}
		}
	}
})();

Wilq32.PhotoEffect.prototype={
    _setupParameters : function (parameters){
		this._parameters = this._parameters || {};
        if (typeof this._angle !== "number") this._angle = 0 ;
        if (typeof parameters.angle==="number") this._angle = parameters.angle;
        this._parameters.animateTo = (typeof parameters.animateTo==="number") ? (parameters.animateTo) : (this._angle); 

        this._parameters.step = parameters.step || this._parameters.step || null;
		this._parameters.easing = parameters.easing || this._parameters.easing || function (x, t, b, c, d) { return -c * ((t=t/d-1)*t*t*t - 1) + b; }
		this._parameters.duration = parameters.duration || this._parameters.duration || 1000;
        this._parameters.callback = parameters.callback || this._parameters.callback || function(){};
        if (parameters.bind && parameters.bind != this._parameters.bind) this._BindEvents(parameters.bind); 
	},
	_handleRotation : function(parameters){
          this._setupParameters(parameters);
          if (this._angle==this._parameters.animateTo) {
              this._rotate(this._angle);
          }
          else { 
              this._animateStart();          
          }
	},

	_BindEvents:function(events){
		if (events && this._eventObj) 
		{
            // Unbinding previous Events
            if (this._parameters.bind){
                var oldEvents = this._parameters.bind;
                for (var a in oldEvents) if (oldEvents.hasOwnProperty(a)) 
                        // TODO: Remove jQuery dependency
                        jQuery(this._eventObj).unbind(a,oldEvents[a]);
            }

            this._parameters.bind = events;
			for (var a in events) if (events.hasOwnProperty(a)) 
				// TODO: Remove jQuery dependency
					jQuery(this._eventObj).bind(a,events[a]);
		}
	},

	_Loader:(function()
	{
		if (IE)
		return function(parameters)
		{
			var width=this._img.width;
			var height=this._img.height;
			this._img.parentNode.removeChild(this._img);
							
			this._vimage = this.createVMLNode('image');
			this._vimage.src=this._img.src;
			this._vimage.style.height=height+"px";
			this._vimage.style.width=width+"px";
			this._vimage.style.position="absolute"; // FIXES IE PROBLEM - its only rendered if its on absolute position!
			this._vimage.style.top = "0px";
			this._vimage.style.left = "0px";

			/* Group minifying a small 1px precision problem when rotating object */
			this._container =  this.createVMLNode('group');
			this._container.style.width=width;
			this._container.style.height=height;
			this._container.style.position="absolute";
			this._container.setAttribute('coordsize',width-1+','+(height-1)); // This -1, -1 trying to fix ugly problem with small displacement on IE
			this._container.appendChild(this._vimage);
			
			this._rootObj.appendChild(this._container);
			this._rootObj.style.position="relative"; // FIXES IE PROBLEM
			this._rootObj.style.width=width+"px";
			this._rootObj.style.height=height+"px";
			this._rootObj.setAttribute('id',this._img.getAttribute('id'));
			this._rootObj.className=this._img.className;			
		    this._eventObj = this._rootObj;	
		    this._handleRotation(parameters);	
		}
		else
		return function (parameters)
		{
			this._rootObj.setAttribute('id',this._img.getAttribute('id'));
			this._rootObj.className=this._img.className;
			
			this._width=this._img.width;
			this._height=this._img.height;
			this._widthHalf=this._width/2; // used for optimisation
			this._heightHalf=this._height/2;// used for optimisation
			
			var _widthMax=Math.sqrt((this._height)*(this._height) + (this._width) * (this._width));

			this._widthAdd = _widthMax - this._width;
			this._heightAdd = _widthMax - this._height;	// widthMax because maxWidth=maxHeight
			this._widthAddHalf=this._widthAdd/2; // used for optimisation
			this._heightAddHalf=this._heightAdd/2;// used for optimisation
			
			this._img.parentNode.removeChild(this._img);	
			
			this._aspectW = ((parseInt(this._img.style.width,10)) || this._width)/this._img.width;
			this._aspectH = ((parseInt(this._img.style.height,10)) || this._height)/this._img.height;
			
			this._canvas=document.createElement('canvas');
			this._canvas.setAttribute('width',this._width);
			this._canvas.style.position="relative";
			this._canvas.style.left = -this._widthAddHalf + "px";
			this._canvas.style.top = -this._heightAddHalf + "px";
			this._canvas.Wilq32 = this._rootObj.Wilq32;
			
			this._rootObj.appendChild(this._canvas);
			this._rootObj.style.width=this._width+"px";
			this._rootObj.style.height=this._height+"px";
            this._eventObj = this._canvas;
			
			this._cnv=this._canvas.getContext('2d');
            this._handleRotation(parameters);
		}
	})(),

	_animateStart:function()
	{	
		if (this._timer) {
			clearTimeout(this._timer);
		}
		this._animateStartTime = +new Date;
		this._animateStartAngle = this._angle;
		this._animate();
	},
    _animate:function()
    {
         var actualTime = +new Date;
         var checkEnd = actualTime - this._animateStartTime > this._parameters.duration;

         // TODO: Bug for animatedGif for static rotation ? (to test)
         if (checkEnd && !this._parameters.animatedGif) 
         {
             clearTimeout(this._timer);
         }
         else 
         {
             if (this._canvas||this._vimage||this._img) {
                 var angle = this._parameters.easing(0, actualTime - this._animateStartTime, this._animateStartAngle, this._parameters.animateTo - this._animateStartAngle, this._parameters.duration);
                 this._rotate((~~(angle*10))/10);
             }
             if (this._parameters.step) {
                this._parameters.step(this._angle);
             }
             var self = this;
             this._timer = setTimeout(function()
                     {
                     self._animate.call(self);
                     }, 10);
         }

         // To fix Bug that prevents using recursive function in callback I moved this function to back
         if (this._parameters.callback && checkEnd){
             this._angle = this._parameters.animateTo;
             this._rotate(this._angle);
             this._parameters.callback.call(this._rootObj);
         }
     },

	_rotate : (function()
	{
		var rad = Math.PI/180;
		if (IE)
		return function(angle)
		{
            this._angle = angle;
			this._container.style.rotation=(angle%360)+"deg";
		}
		else if (supportedCSS)
		return function(angle){
            this._angle = angle;
			this._img.style[supportedCSS]="rotate("+(angle%360)+"deg)";
		}
		else 
		return function(angle)
		{
            this._angle = angle;
			angle=(angle%360)* rad;
			// clear canvas	
			this._canvas.width = this._width+this._widthAdd;
			this._canvas.height = this._height+this._heightAdd;
						
			// REMEMBER: all drawings are read from backwards.. so first function is translate, then rotate, then translate, translate..
			this._cnv.translate(this._widthAddHalf,this._heightAddHalf);	// at least center image on screen
			this._cnv.translate(this._widthHalf,this._heightHalf);			// we move image back to its orginal 
			this._cnv.rotate(angle);										// rotate image
			this._cnv.translate(-this._widthHalf,-this._heightHalf);		// move image to its center, so we can rotate around its center
			this._cnv.scale(this._aspectW,this._aspectH); // SCALE - if needed ;)
			this._cnv.drawImage(this._img, 0, 0);							// First - we draw image
		}

	})()
}

if (IE)
{
Wilq32.PhotoEffect.prototype.createVMLNode=(function(){
document.createStyleSheet().addRule(".rvml", "behavior:url(#default#VML)");
		try {
			!document.namespaces.rvml && document.namespaces.add("rvml", "urn:schemas-microsoft-com:vml");
			return function (tagName) {
				return document.createElement('<rvml:' + tagName + ' class="rvml">');
			};
		} catch (e) {
			return function (tagName) {
				return document.createElement('<' + tagName + ' xmlns="urn:schemas-microsoft.com:vml" class="rvml">');
			};
		}		
})();
}

})(jQuery);
