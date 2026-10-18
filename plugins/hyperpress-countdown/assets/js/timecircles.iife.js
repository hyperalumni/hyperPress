var TimeCircles = (function(exports, jquery) {
	Object.defineProperty(exports, Symbol.toStringTag, { value: "Module" });
	//#region \0rolldown/runtime.js
	var __create = Object.create;
	var __defProp = Object.defineProperty;
	var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
	var __getOwnPropNames = Object.getOwnPropertyNames;
	var __getProtoOf = Object.getPrototypeOf;
	var __hasOwnProp = Object.prototype.hasOwnProperty;
	var __copyProps = (to, from, except, desc) => {
		if (from && typeof from === "object" || typeof from === "function") for (var keys = __getOwnPropNames(from), i = 0, n = keys.length, key; i < n; i++) {
			key = keys[i];
			if (!__hasOwnProp.call(to, key) && key !== except) __defProp(to, key, {
				get: ((k) => from[k]).bind(null, key),
				enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable
			});
		}
		return to;
	};
	var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(isNodeMode || !mod || !mod.__esModule || !__hasOwnProp.call(mod, "default") ? __defProp(target, "default", {
		value: mod,
		enumerable: true
	}) : target, mod));
	//#endregion
	jquery = __toESM(jquery);
	//#region src/TimeCircles.ts
	/**
	* Basic structure: TC_Class is the public class that is returned upon being called
	*
	* So, if you do
	*      var tc = $(".timer").TimeCircles();
	*
	* tc will contain an instance of the public TimeCircles class. It is important to
	* note that TimeCircles is not chained in the conventional way, check the
	* documentation for more info on how TimeCircles can be chained.
	*
	* After being called/created, the public TimerCircles class will then- for each element
	* within it's collection, either fetch or create an instance of the private class.
	* Each function called upon the public class will be forwarded to each instance
	* of the private classes within the relevant element collection
	**/
	var useWindow = window;
	var tick_duration = 200;
	location.hash;
	var allUnits = [
		"Days",
		"Hours",
		"Minutes",
		"Seconds"
	];
	var nextUnits = {
		Seconds: "Minutes",
		Minutes: "Hours",
		Hours: "Days",
		Days: "Years"
	};
	var secondsIn = {
		Seconds: 1,
		Minutes: 60,
		Hours: 3600,
		Days: 86400,
		Months: 2678400,
		Years: 31536e3
	};
	/**
	* Converts hex color code into object containing integer values for the r,g,b use
	* This function (hexToRgb) originates from:
	* http://stackoverflow.com/questions/5623838/rgb-to-hex-and-hex-to-rgb
	* @param {string} hex color code
	*/
	function hexToRgb(hex) {
		var rgba = /^rgba?\(([\d]+),([\d]+),([\d]+)(,([\d\.]+))?\)$/;
		if (rgba.test(hex)) {
			var result = rgba.exec(hex);
			return {
				r: parseInt(result[1]),
				g: parseInt(result[2]),
				b: parseInt(result[3]),
				a: parseInt(String(result[5] ? result[5] : 1))
			};
		}
		hex = hex.replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i, function(m, r, g, b) {
			return r + r + g + g + b + b;
		});
		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
	}
	function isCanvasSupported() {
		var elem = document.createElement("canvas");
		return !!(elem.getContext && elem.getContext("2d"));
	}
	/**
	* Function s4() and guid() originate from:
	* http://stackoverflow.com/questions/105034/how-to-create-a-guid-uuid-in-javascript
	*/
	function s4() {
		return Math.floor((1 + Math.random()) * 65536).toString(16).substring(1);
	}
	/**
	* Creates a unique id
	* @returns {String}
	*/
	function guid() {
		return s4() + s4() + "-" + s4() + "-" + s4() + "-" + s4() + "-" + s4() + s4() + s4();
	}
	function parse_date(str) {
		var match = str.match(/^[0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{1,2}:[0-9]{2}:[0-9]{2}$/);
		if (match !== null && match.length > 0) {
			var parts = str.split(" ");
			var date = parts[0].split("-");
			var time = parts[1].split(":");
			return new Date(Number(date[0]), Number(date[1]) - 1, Number(date[2]), Number(time[0]), Number(time[1]), Number(time[2]));
		}
		var d = Date.parse(str);
		if (!isNaN(d)) return d;
		d = Date.parse(str.replace(/-/g, "/").replace("T", " "));
		if (!isNaN(d)) return d;
		return /* @__PURE__ */ new Date();
	}
	function parse_times(diff, old_diff, total_duration, units, floor) {
		var raw_time = {};
		var raw_old_time = {};
		var time = {};
		var pct = {};
		var old_pct = {};
		var old_time = {};
		var greater_unit = null;
		for (var i = 0; i < units.length; i++) {
			var unit = units[i];
			var maxUnits;
			if (greater_unit === null) maxUnits = total_duration / secondsIn[unit];
			else maxUnits = secondsIn[greater_unit] / secondsIn[unit];
			var curUnits = diff / secondsIn[unit];
			var oldUnits = old_diff / secondsIn[unit];
			if (floor) {
				if (curUnits > 0) curUnits = Math.floor(curUnits);
				else curUnits = Math.ceil(curUnits);
				if (oldUnits > 0) oldUnits = Math.floor(oldUnits);
				else oldUnits = Math.ceil(oldUnits);
			}
			if (unit !== "Days") {
				curUnits = curUnits % maxUnits;
				oldUnits = oldUnits % maxUnits;
			}
			raw_time[unit] = curUnits;
			time[unit] = Math.abs(curUnits);
			raw_old_time[unit] = oldUnits;
			old_time[unit] = Math.abs(oldUnits);
			pct[unit] = Math.abs(curUnits) / maxUnits;
			old_pct[unit] = Math.abs(oldUnits) / maxUnits;
			greater_unit = unit;
		}
		return {
			raw_time,
			raw_old_time,
			time,
			old_time,
			pct,
			old_pct
		};
	}
	var TC_Instance_List = {};
	function updateUsedWindow() {
		if (typeof useWindow.TC_Instance_List !== "undefined") TC_Instance_List = useWindow.TC_Instance_List;
		else useWindow.TC_Instance_List = TC_Instance_List;
	}
	var default_options = {
		ref_date: /* @__PURE__ */ new Date(),
		start: true,
		animation: "smooth",
		count_past_zero: true,
		circle_bg_color: "#60686F",
		use_background: true,
		fg_width: .1,
		bg_width: 1.2,
		text_size: .07,
		number_size: .28,
		total_duration: "Auto",
		direction: "Clockwise",
		use_top_frame: false,
		start_angle: 0,
		time: {
			Days: {
				show: true,
				text: "Days",
				color: "#FC6"
			},
			Hours: {
				show: true,
				text: "Hours",
				color: "#9CF"
			},
			Minutes: {
				show: true,
				text: "Minutes",
				color: "#BFB"
			},
			Seconds: {
				show: true,
				text: "Seconds",
				color: "#F99"
			}
		}
	};
	var TC_Instance = class {
		constructor(element, options) {
			this.listeners = null;
			this.config = null;
			this.element = element;
			this.listeners = null;
			this.data = {
				paused: false,
				last_frame: 0,
				animation_frame: null,
				update_timeout: null,
				draw_frames: [],
				fade_timeouts: [],
				interval_fallback: null,
				timer: false,
				total_duration: null,
				prev_time: null,
				drawn_units: [],
				text_elements: {
					Days: null,
					Hours: null,
					Minutes: null,
					Seconds: null
				},
				attributes: {
					canvas: null,
					context: null,
					item_size: null,
					line_width: null,
					radius: null,
					outer_radius: null
				},
				state: { fading: {
					Days: false,
					Hours: false,
					Minutes: false,
					Seconds: false
				} }
			};
			this.config = null;
			this.setOptions(options);
			this.initialize();
		}
		clearListeners() {
			this.listeners = {
				all: [],
				visible: []
			};
		}
		addTime(seconds_to_add) {
			if (this.data.attributes.ref_date instanceof Date) {
				var d = this.data.attributes.ref_date;
				d.setSeconds(d.getSeconds() + seconds_to_add);
			} else if (!isNaN(this.data.attributes.ref_date)) this.data.attributes.ref_date += seconds_to_add * 1e3;
		}
		initialize(clear_listeners) {
			this.data.drawn_units = [];
			for (var i = 0; i < Object.keys(this.config.time).length; i++) {
				var unit = Object.keys(this.config.time)[i];
				if (this.config.time[unit].show) this.data.drawn_units.push(unit);
			}
			(0, jquery.default)(this.element).children("div.time_circles").remove();
			this.cancelTimers();
			if (typeof clear_listeners === "undefined") clear_listeners = true;
			if (clear_listeners || this.listeners === null) this.clearListeners();
			this.container = (0, jquery.default)("<div>");
			this.container.addClass("time_circles");
			this.container.appendTo(this.element);
			var height = this.element.offsetHeight;
			var width = this.element.offsetWidth;
			if (height === 0) height = (0, jquery.default)(this.element).height();
			if (width === 0) width = (0, jquery.default)(this.element).width();
			if (height === 0 && width > 0) height = width / this.data.drawn_units.length;
			else if (width === 0 && height > 0) width = height * this.data.drawn_units.length;
			var canvasElement = document.createElement("canvas");
			canvasElement.width = width;
			canvasElement.height = height;
			this.data.attributes.canvas = (0, jquery.default)(canvasElement);
			this.data.attributes.canvas.appendTo(this.container);
			if (isCanvasSupported()) this.data.attributes.context = canvasElement.getContext("2d");
			this.data.attributes.item_size = Math.min(width / this.data.drawn_units.length, height);
			this.data.attributes.line_width = this.data.attributes.item_size * this.config.fg_width;
			this.data.attributes.radius = (this.data.attributes.item_size * .8 - this.data.attributes.line_width) / 2;
			this.data.attributes.outer_radius = this.data.attributes.radius + .5 * Math.max(this.data.attributes.line_width, this.data.attributes.line_width * this.config.bg_width);
			var i = 0;
			for (var key in this.data.text_elements) {
				if (!this.config.time[key].show) continue;
				var textElement = (0, jquery.default)("<div>");
				textElement.addClass("textDiv_" + key);
				textElement.css("top", Math.round(.35 * this.data.attributes.item_size));
				textElement.css("left", Math.round(i++ * this.data.attributes.item_size));
				textElement.css("width", this.data.attributes.item_size);
				textElement.appendTo(this.container);
				var headerElement = (0, jquery.default)("<h4>");
				headerElement.text(this.config.time[key].text);
				headerElement.css("font-size", Math.round(this.config.text_size * this.data.attributes.item_size));
				headerElement.appendTo(textElement);
				var numberElement = (0, jquery.default)("<span>");
				numberElement.css("font-size", Math.round(this.config.number_size * this.data.attributes.item_size));
				numberElement.appendTo(textElement);
				this.data.text_elements[key] = numberElement;
			}
			this.start();
			if (!this.config.start) this.data.paused = true;
			var _this = this;
			useWindow.clearInterval(this.data.interval_fallback);
			this.data.interval_fallback = useWindow.setInterval(function() {
				_this.update.call(_this, true);
			}, 100);
		}
		update(nodraw) {
			if (typeof nodraw === "undefined") nodraw = false;
			else if (nodraw && this.data.paused) return;
			var diff, old_diff;
			var prevDate = this.data.prev_time;
			var curDate = /* @__PURE__ */ new Date();
			this.data.prev_time = curDate;
			if (prevDate === null) prevDate = curDate;
			if (!this.config.count_past_zero) {
				if (curDate > this.data.attributes.ref_date) {
					for (var i = 0; i < this.data.drawn_units.length; i++) {
						var key = this.data.drawn_units[i];
						this.data.text_elements[key].text("0");
						var x = i * this.data.attributes.item_size + this.data.attributes.item_size / 2;
						var y = this.data.attributes.item_size / 2;
						var color = this.config.time[key].color;
						this.drawArc(x, y, color, 0);
					}
					this.stop();
					return;
				}
			}
			diff = (this.data.attributes.ref_date - +curDate) / 1e3;
			old_diff = (this.data.attributes.ref_date - +prevDate) / 1e3;
			var floor = this.config.animation !== "smooth";
			var visible_times = parse_times(diff, old_diff, this.data.total_duration, this.data.drawn_units, floor);
			var all_times = parse_times(diff, old_diff, secondsIn["Years"], allUnits, floor);
			var i = 0;
			var j = 0;
			var lastKey = null;
			var cur_shown = this.data.drawn_units.slice();
			for (var ui in allUnits) {
				var key = allUnits[ui];
				if (Math.floor(all_times.raw_time[key]) !== Math.floor(all_times.raw_old_time[key])) this.notifyListeners(key, Math.floor(all_times.time[key]), Math.floor(diff), "all");
				if (cur_shown.indexOf(key) < 0) continue;
				if (Math.floor(visible_times.raw_time[key]) !== Math.floor(visible_times.raw_old_time[key])) this.notifyListeners(key, Math.floor(visible_times.time[key]), Math.floor(diff), "visible");
				if (!nodraw) {
					this.data.text_elements[key].text(Math.floor(Math.abs(visible_times.time[key])));
					var x = j * this.data.attributes.item_size + this.data.attributes.item_size / 2;
					var y = this.data.attributes.item_size / 2;
					var color = this.config.time[key].color;
					if (this.config.animation === "smooth") {
						if (lastKey !== null) {
							if (Math.floor(visible_times.time[lastKey]) > Math.floor(visible_times.old_time[lastKey])) {
								this.radialFade(x, y, color, 1, key);
								this.data.state.fading[key] = true;
							} else if (Math.floor(visible_times.time[lastKey]) < Math.floor(visible_times.old_time[lastKey])) {
								this.radialFade(x, y, color, 0, key);
								this.data.state.fading[key] = true;
							}
						}
						if (!this.data.state.fading[key]) this.drawArc(x, y, color, visible_times.pct[key]);
					} else this.animateArc(x, y, color, visible_times.pct[key], visible_times.old_pct[key], (/* @__PURE__ */ new Date()).getTime() + tick_duration);
				}
				lastKey = key;
				j++;
			}
			if (this.data.paused || nodraw) return;
			var _this = this;
			var update = function() {
				_this.update.call(_this);
			};
			if (this.config.animation === "smooth") this.data.animation_frame = useWindow.requestAnimationFrame(update);
			else {
				var delay = diff % 1 * 1e3;
				if (delay < 0) delay = 1e3 + delay;
				delay += 50;
				_this.data.update_timeout = useWindow.setTimeout(function() {
					_this.data.update_timeout = null;
					_this.data.animation_frame = useWindow.requestAnimationFrame(update);
				}, delay);
			}
		}
		animateArc(x, y, color, target_pct, cur_pct, animation_end) {
			if (this.data.attributes.context === null) return;
			var diff = cur_pct - target_pct;
			if (Math.abs(diff) > .5) {
				if (target_pct === 0) this.radialFade(x, y, color, 1);
				else this.radialFade(x, y, color, 0);
			} else {
				var progress = (tick_duration - (animation_end - (/* @__PURE__ */ new Date()).getTime())) / tick_duration;
				if (progress > 1) progress = 1;
				var pct = cur_pct * (1 - progress) + target_pct * progress;
				this.drawArc(x, y, color, pct);
				if (progress >= 1) return;
				var _this = this;
				var frame_id = useWindow.requestAnimationFrame(function() {
					_this.untrack(_this.data.draw_frames, frame_id);
					_this.animateArc(x, y, color, target_pct, cur_pct, animation_end);
				});
				this.data.draw_frames.push(frame_id);
			}
		}
		drawArc(x, y, color, pct) {
			if (this.data.attributes.context === null) return;
			var clear_radius = Math.max(this.data.attributes.outer_radius, this.data.attributes.item_size / 2);
			this.data.attributes.context.clearRect(x - clear_radius, y - clear_radius, clear_radius * 2, clear_radius * 2);
			if (this.config.use_background) {
				this.data.attributes.context.beginPath();
				this.data.attributes.context.arc(x, y, this.data.attributes.radius, 0, 2 * Math.PI, false);
				this.data.attributes.context.lineWidth = this.data.attributes.line_width * this.config.bg_width;
				this.data.attributes.context.strokeStyle = this.config.circle_bg_color;
				this.data.attributes.context.stroke();
			}
			var startAngle, endAngle, counterClockwise;
			var defaultOffset = -.5 * Math.PI;
			var fullCircle = 2 * Math.PI;
			startAngle = defaultOffset + this.config.start_angle / 360 * fullCircle;
			var offset = 2 * pct * Math.PI;
			if (this.config.direction === "Both") {
				counterClockwise = false;
				startAngle -= offset / 2;
				endAngle = startAngle + offset;
			} else if (this.config.direction === "Clockwise") {
				counterClockwise = false;
				endAngle = startAngle + offset;
			} else {
				counterClockwise = true;
				endAngle = startAngle - offset;
			}
			this.data.attributes.context.beginPath();
			this.data.attributes.context.arc(x, y, this.data.attributes.radius, startAngle, endAngle, counterClockwise);
			this.data.attributes.context.lineWidth = this.data.attributes.line_width;
			this.data.attributes.context.strokeStyle = color;
			this.data.attributes.context.stroke();
		}
		radialFade(x, y, color, from, key) {
			var rgb = hexToRgb(color);
			var _this = this;
			var step = .2 * (from === 1 ? -1 : 1);
			var i = 0;
			for (; from <= 1 && from >= 0; i++) {
				(function() {
					var delay = 50 * i;
					var rgba = "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", " + Math.round(from * 10) / 10 + ")";
					var fade_id = useWindow.setTimeout(function() {
						_this.untrack(_this.data.fade_timeouts, fade_id);
						_this.drawArc(x, y, rgba, 1);
					}, delay);
					_this.data.fade_timeouts.push(fade_id);
				})();
				from += step;
			}
			var done_id = useWindow.setTimeout(function() {
				_this.untrack(_this.data.fade_timeouts, done_id);
				_this.data.state.fading[key] = false;
			}, 50 * i);
			this.data.fade_timeouts.push(done_id);
		}
		untrack(list, id) {
			var index = list.indexOf(id);
			if (index !== -1) list.splice(index, 1);
		}
		cancelTimers() {
			useWindow.cancelAnimationFrame(this.data.animation_frame);
			this.data.animation_frame = null;
			useWindow.clearTimeout(this.data.update_timeout);
			this.data.update_timeout = null;
			var i = 0;
			for (; i < this.data.draw_frames.length; i++) useWindow.cancelAnimationFrame(this.data.draw_frames[i]);
			this.data.draw_frames = [];
			for (i = 0; i < this.data.fade_timeouts.length; i++) useWindow.clearTimeout(this.data.fade_timeouts[i]);
			this.data.fade_timeouts = [];
			for (var key in this.data.state.fading) this.data.state.fading[key] = false;
		}
		timeLeft() {
			if (this.data.paused && typeof this.data.timer === "number") return this.data.timer;
			var now = /* @__PURE__ */ new Date();
			return (this.data.attributes.ref_date - +now) / 1e3;
		}
		start() {
			this.cancelTimers();
			var attr_data_date = (0, jquery.default)(this.element).data("date");
			if (typeof attr_data_date === "undefined") attr_data_date = (0, jquery.default)(this.element).attr("data-date");
			if (typeof attr_data_date === "string") this.data.attributes.ref_date = parse_date(attr_data_date);
			else if (typeof this.data.timer === "number") {
				if (this.data.paused) this.data.attributes.ref_date = (/* @__PURE__ */ new Date()).getTime() + this.data.timer * 1e3;
			} else {
				var attr_data_timer = (0, jquery.default)(this.element).data("timer");
				if (typeof attr_data_timer === "undefined") attr_data_timer = (0, jquery.default)(this.element).attr("data-timer");
				if (typeof attr_data_timer === "string") attr_data_timer = parseFloat(attr_data_timer);
				if (typeof attr_data_timer === "number") {
					this.data.timer = attr_data_timer;
					this.data.attributes.ref_date = (/* @__PURE__ */ new Date()).getTime() + attr_data_timer * 1e3;
				} else this.data.attributes.ref_date = this.config.ref_date;
			}
			this.data.paused = false;
			this.update.call(this);
		}
		restart() {
			this.data.timer = false;
			this.start();
		}
		stop() {
			if (typeof this.data.timer === "number") this.data.timer = this.timeLeft();
			this.data.paused = true;
			this.cancelTimers();
		}
		destroy() {
			this.clearListeners();
			this.stop();
			useWindow.clearInterval(this.data.interval_fallback);
			this.data.interval_fallback = null;
			this.container.remove();
			var tc_id = (0, jquery.default)(this.element).attr("data-tc-id");
			if (typeof tc_id === "undefined") tc_id = (0, jquery.default)(this.element).data("tc-id");
			if (typeof tc_id !== "undefined" && TC_Instance_List[tc_id] === this) delete TC_Instance_List[tc_id];
			(0, jquery.default)(this.element).removeAttr("data-tc-id");
			(0, jquery.default)(this.element).removeData("tc-id");
		}
		setOptions(options) {
			if (this.config === null) {
				default_options.ref_date = /* @__PURE__ */ new Date();
				this.config = jquery.default.extend(true, {}, default_options);
			}
			jquery.default.extend(true, this.config, options);
			if (this.config.use_top_frame) useWindow = window.top;
			else useWindow = window;
			updateUsedWindow();
			this.data.total_duration = this.config.total_duration;
			if (typeof this.data.total_duration === "string") {
				if (typeof secondsIn[this.data.total_duration] !== "undefined") this.data.total_duration = secondsIn[this.data.total_duration];
				else if (this.data.total_duration === "Auto") for (var i = 0; i < Object.keys(this.config.time).length; i++) {
					var unit = Object.keys(this.config.time)[i];
					if (this.config.time[unit].show) {
						this.data.total_duration = secondsIn[nextUnits[unit]];
						break;
					}
				}
				else {
					this.data.total_duration = secondsIn["Years"];
					console.error("Valid values for TimeCircles config.total_duration are either numeric, or (string) Years, Months, Days, Hours, Minutes, Auto");
				}
			}
		}
		addListener(f, context, type) {
			if (typeof f !== "function") return;
			if (typeof type === "undefined") type = "visible";
			this.listeners[type].push({
				func: f,
				scope: context
			});
		}
		notifyListeners(unit, value, total, type) {
			for (var i = 0; i < this.listeners[type].length; i++) {
				var listener = this.listeners[type][i];
				listener.func.apply(listener.scope, [
					unit,
					value,
					total
				]);
			}
		}
	};
	var TC_Class = class {
		constructor(elements, options) {
			this.elements = elements;
			this.options = options;
			this.foreach();
		}
		getInstance(element) {
			var instance;
			var cur_id = (0, jquery.default)(element).data("tc-id");
			if (typeof cur_id === "undefined") {
				cur_id = guid();
				(0, jquery.default)(element).attr("data-tc-id", cur_id);
			}
			if (typeof TC_Instance_List[cur_id] === "undefined") {
				var options = this.options;
				var element_options = (0, jquery.default)(element).data("options");
				if (typeof element_options === "string") element_options = JSON.parse(element_options);
				if (typeof element_options === "object") options = jquery.default.extend(true, {}, this.options, element_options);
				instance = new TC_Instance(element, options);
				TC_Instance_List[cur_id] = instance;
			} else {
				instance = TC_Instance_List[cur_id];
				if (typeof this.options !== "undefined") instance.setOptions(this.options);
			}
			return instance;
		}
		addTime(seconds_to_add) {
			this.foreach(function(instance) {
				instance.addTime(seconds_to_add);
			});
		}
		foreach(callback) {
			var _this = this;
			this.elements.each(function() {
				var instance = _this.getInstance(this);
				if (typeof callback === "function") callback(instance);
			});
			return this;
		}
		start() {
			this.foreach(function(instance) {
				instance.start();
			});
			return this;
		}
		stop() {
			this.foreach(function(instance) {
				instance.stop();
			});
			return this;
		}
		restart() {
			this.foreach(function(instance) {
				instance.restart();
			});
			return this;
		}
		rebuild() {
			this.foreach(function(instance) {
				instance.initialize(false);
			});
			return this;
		}
		getTime() {
			return this.getInstance(this.elements[0]).timeLeft();
		}
		addListener(f, type) {
			if (typeof type === "undefined") type = "visible";
			var _this = this;
			this.foreach(function(instance) {
				instance.addListener(f, _this.elements, type);
			});
			return this;
		}
		destroy() {
			this.foreach(function(instance) {
				instance.destroy();
			});
			return this;
		}
		end() {
			return this.elements;
		}
	};
	jquery.default.fn.TimeCircles = function(options) {
		return new TC_Class(this, options);
	};
	//#endregion
	exports.TC_Class = TC_Class;
	return exports;
})({}, jQuery);
