(function ($) {

	"use strict";

	// Uses code from ColorTunes available here:
	// https://github.com/dannvix/ColorTunes

	var MMCQ, PriorityQueue, ColorTunes;

	PriorityQueue = (function() {
		function PriorityQueue(comparator) {
			this.comparator = comparator;
			this.contents = [];
			this.sorted = false;
		}

		PriorityQueue.prototype.sort = function() {
			this.contents.sort(this.comparator);
			return this.sotred = true;
		};

		PriorityQueue.prototype.push = function(obj) {
			this.contents.push(obj);
			return this.sorted = false;
		};

		PriorityQueue.prototype.peek = function(index) {
			if (index == null) {
				index = this.contents.length - 1;
			}
			if (!this.sorted) {
				this.sort();
			}
			return this.contents[index];
		};

		PriorityQueue.prototype.pop = function() {
			if (!this.sorted) {
				this.sort();
			}
			return this.contents.pop();
		};

		PriorityQueue.prototype.size = function() {
			return this.contents.length;
		};

		PriorityQueue.prototype.map = function(func) {
			return this.contents.map(func);
		};

		return PriorityQueue;

	})();

	MMCQ = (function() {
		var ColorBox, ColorMap, cboxFromPixels, getColorIndex, getHisto, medianCutApply;

		MMCQ.sigbits = 5;

		MMCQ.rshift = 8 - MMCQ.sigbits;

		function MMCQ() {
			this.maxIterations = 1000;
			this.fractByPopulations = 0.75;
		}

		getColorIndex = function(r, g, b) {
			return (r << (2 * MMCQ.sigbits)) + (g << MMCQ.sigbits) + b;
		};

		ColorBox = (function() {
			function ColorBox(r1, r2, g1, g2, b1, b2, histo) {
				this.r1 = r1;
				this.r2 = r2;
				this.g1 = g1;
				this.g2 = g2;
				this.b1 = b1;
				this.b2 = b2;
				this.histo = histo;
			}

			ColorBox.prototype.volume = function(forced) {
				if (!this._volume || forced) {
					this._volume = (this.r2 - this.r1 + 1) * (this.g2 - this.g1 + 1) * (this.b2 - this.b1 + 1);
				}
				return this._volume;
			};

			ColorBox.prototype.count = function(forced) {
				var b, g, index, numpix, r, _i, _j, _k, _ref, _ref1, _ref2, _ref3, _ref4, _ref5;
				if (!this._count_set || forced) {
					numpix = 0;
					for (r = _i = _ref = this.r1, _ref1 = this.r2; _i <= _ref1; r = _i += 1) {
						for (g = _j = _ref2 = this.g1, _ref3 = this.g2; _j <= _ref3; g = _j += 1) {
							for (b = _k = _ref4 = this.b1, _ref5 = this.b2; _k <= _ref5; b = _k += 1) {
								index = getColorIndex(r, g, b);
								numpix += this.histo[index] || 0;
							}
						}
					}
					this._count_set = true;
					this._count = numpix;
				}
				return this._count;
			};

			ColorBox.prototype.copy = function() {
				return new ColorBox(this.r1, this.r2, this.g1, this.g2, this.b1, this.b2, this.histo);
			};

			ColorBox.prototype.average = function(forced) {
				var b, bsum, g, gsum, hval, index, mult, r, rsum, total, _i, _j, _k, _ref, _ref1, _ref2, _ref3, _ref4, _ref5;
				if (!this._average || forced) {
					mult = 1 << (8 - MMCQ.sigbits);
					total = 0;
					rsum = 0;
					gsum = 0;
					bsum = 0;
					for (r = _i = _ref = this.r1, _ref1 = this.r2; _i <= _ref1; r = _i += 1) {
						for (g = _j = _ref2 = this.g1, _ref3 = this.g2; _j <= _ref3; g = _j += 1) {
							for (b = _k = _ref4 = this.b1, _ref5 = this.b2; _k <= _ref5; b = _k += 1) {
								index = getColorIndex(r, g, b);
								hval = this.histo[index] || 0;
								total += hval;
								rsum += hval * (r + 0.5) * mult;
								gsum += hval * (g + 0.5) * mult;
								bsum += hval * (b + 0.5) * mult;
							}
						}
					}
					if (total) {
						this._average = [~~(rsum / total), ~~(gsum / total), ~~(bsum / total)];
					} else {
						this._average = [~~(mult * (this.r1 + this.r2 + 1) / 2), ~~(mult * (this.g1 + this.g2 + 1) / 2), ~~(mult * (this.b1 + this.b2 + 1) / 2)];
					}
				}
				return this._average;
			};

			ColorBox.prototype.contains = function(pixel) {
				var b, g, r;
				r = pixel[0] >> MMCQ.rshift;
				g = pixel[1] >> MMCQ.rshift;
				b = pixel[2] >> MMCQ.rshift;
				return ((this.r1 <= r && r <= this.r2)) && ((this.g1 <= g && g <= this.g2)) && ((this.b1 <= b && b <= this.b2));
			};

			return ColorBox;

		})();

		ColorMap = (function() {
			function ColorMap() {
				this.cboxes = new PriorityQueue(function(a, b) {
					var va, vb;
					va = a.count() * a.volume();
					vb = b.count() * b.volume();
					if (va > vb) {
						return 1;
					} else if (va < vb) {
						return -1;
					} else {
						return 0;
					}
				});
			}

			ColorMap.prototype.push = function(cbox) {
				return this.cboxes.push({
					cbox: cbox,
					color: cbox.average()
				});
			};

			ColorMap.prototype.palette = function() {
				return this.cboxes.map(function(cbox) {
					return cbox.color;
				});
			};

			ColorMap.prototype.size = function() {
				return this.cboxes.size();
			};

			ColorMap.prototype.map = function(color) {
				var i, _i, _ref;
				for (i = _i = 0, _ref = this.cboxes.size(); _i < _ref; i = _i += 1) {
					if (this.cboxes.peek(i).cbox.contains(color)) {
						return this.cboxes.peek(i).color;
					}
					return this.nearest(color);
				}
			};

			ColorMap.prototype.cboxes = function() {
				return this.cboxes;
			};

			ColorMap.prototype.nearest = function(color) {
				var dist, i, minDist, retColor, square, _i, _ref;
				square = function(n) {
					return n * n;
				};
				minDist = 1e9;
				for (i = _i = 0, _ref = this.cboxes.size(); _i < _ref; i = _i += 1) {
					dist = Math.sqrt(square(color[0] - this.cboxes.peek(i).color[0]) + square(color[1] - this.cboxes.peek(i).color[1]) + square(color[2] - this.cboxes.peek(i).color[2]));
					if (dist < minDist) {
						minDist = dist;
						retColor = this.cboxes.peek(i).color;
					}
				}
				return retColor;
			};

			return ColorMap;

		})();

		getHisto = function(pixels) {
			var b, g, histo, histosize, index, pixel, r, _i, _len;
			histosize = 1 << (3 * MMCQ.sigbits);
			histo = new Array(histosize);
			for (_i = 0, _len = pixels.length; _i < _len; _i++) {
				pixel = pixels[_i];
				r = pixel[0] >> MMCQ.rshift;
				g = pixel[1] >> MMCQ.rshift;
				b = pixel[2] >> MMCQ.rshift;
				index = getColorIndex(r, g, b);
				histo[index] = (histo[index] || 0) + 1;
			}
			return histo;
		};

		cboxFromPixels = function(pixels, histo) {
			var b, bmax, bmin, g, gmax, gmin, pixel, r, rmax, rmin, _i, _len;
			rmin = 1e6;
			rmax = 0;
			gmin = 1e6;
			gmax = 0;
			bmin = 1e6;
			bmax = 0;
			for (_i = 0, _len = pixels.length; _i < _len; _i++) {
				pixel = pixels[_i];
				r = pixel[0] >> MMCQ.rshift;
				g = pixel[1] >> MMCQ.rshift;
				b = pixel[2] >> MMCQ.rshift;
				if (r < rmin) {
					rmin = r;
				} else if (r > rmax) {
					rmax = r;
				}
				if (g < gmin) {
					gmin = g;
				} else if (g > gmax) {
					gmax = g;
				}
				if (b < bmin) {
					bmin = b;
				} else if (b > bmax) {
					bmax = b;
				}
			}
			return new ColorBox(rmin, rmax, gmin, gmax, bmin, bmax, histo);
		};

		medianCutApply = function(histo, cbox) {
			var b, bw, doCut, g, gw, index, lookaheadsum, maxw, partialsum, r, rw, sum, total, _i, _j, _k, _l, _m, _n, _o, _p, _q, _ref, _ref1, _ref10, _ref11, _ref12, _ref13, _ref14, _ref15, _ref16, _ref17, _ref2, _ref3, _ref4, _ref5, _ref6, _ref7, _ref8, _ref9;
			if (!cbox.count()) {
				return;
			}
			if (cbox.count() === 1) {
				return [cbox.copy()];
			}
			rw = cbox.r2 - cbox.r1 + 1;
			gw = cbox.g2 - cbox.g1 + 1;
			bw = cbox.b2 - cbox.b1 + 1;
			maxw = Math.max(rw, gw, bw);
			total = 0;
			partialsum = [];
			lookaheadsum = [];
			if (maxw === rw) {
				for (r = _i = _ref = cbox.r1, _ref1 = cbox.r2; _i <= _ref1; r = _i += 1) {
					sum = 0;
					for (g = _j = _ref2 = cbox.g1, _ref3 = cbox.g2; _j <= _ref3; g = _j += 1) {
						for (b = _k = _ref4 = cbox.b1, _ref5 = cbox.b2; _k <= _ref5; b = _k += 1) {
							index = getColorIndex(r, g, b);
							sum += histo[index] || 0;
						}
					}
					total += sum;
					partialsum[r] = total;
				}
			} else if (maxw === gw) {
				for (g = _l = _ref6 = cbox.g1, _ref7 = cbox.g2; _l <= _ref7; g = _l += 1) {
					sum = 0;
					for (r = _m = _ref8 = cbox.r1, _ref9 = cbox.r2; _m <= _ref9; r = _m += 1) {
						for (b = _n = _ref10 = cbox.b1, _ref11 = cbox.b2; _n <= _ref11; b = _n += 1) {
							index = getColorIndex(r, g, b);
							sum += histo[index] || 0;
						}
					}
					total += sum;
					partialsum[g] = total;
				}
			} else {
				for (b = _o = _ref12 = cbox.b1, _ref13 = cbox.b2; _o <= _ref13; b = _o += 1) {
					sum = 0;
					for (r = _p = _ref14 = cbox.r1, _ref15 = cbox.r2; _p <= _ref15; r = _p += 1) {
						for (g = _q = _ref16 = cbox.g1, _ref17 = cbox.g2; _q <= _ref17; g = _q += 1) {
							index = getColorIndex(r, g, b);
							sum += histo[index] || 0;
						}
					}
					total += sum;
					partialsum[b] = total;
				}
			}
			partialsum.forEach(function(d, i) {
				return lookaheadsum[i] = total - d;
			});
			doCut = function(color) {
				var cbox1, cbox2, count2, d2, dim1, dim2, i, left, right, _r, _ref18, _ref19;
				dim1 = color + '1';
				dim2 = color + '2';
				for (i = _r = _ref18 = cbox[dim1], _ref19 = cbox[dim2]; _r <= _ref19; i = _r += 1) {
					if (partialsum[i] > (total / 2)) {
						cbox1 = cbox.copy();
						cbox2 = cbox.copy();
						left = i - cbox[dim1];
						right = cbox[dim2] - i;
						if (left <= right) {
							d2 = Math.min(cbox[dim2] - 1, ~~(i + right / 2));
						} else {
							d2 = Math.max(cbox[dim1], ~~(i - 1 - left / 2));
						}
						while (!partialsum[d2]) {
							d2++;
						}
						count2 = lookaheadsum[d2];
						while (!count2 && partialsum[d2 - 1]) {
							count2 = lookaheadsum[--d2];
						}
						cbox1[dim2] = d2;
						cbox2[dim1] = cbox1[dim2] + 1;
						return [cbox1, cbox2];
					}
				}
			};
			if (maxw === rw) {
				return doCut("r");
			}
			if (maxw === gw) {
				return doCut("g");
			}
			if (maxw === bw) {
				return doCut("b");
			}
		};

		MMCQ.prototype.quantize = function(pixels, maxcolors) {
			var cbox, cmap, histo, iter, pq, pq2,
				_this = this;
			if ((!pixels.length) || (maxcolors < 2) || (maxcolors > 256)) {
				console.log("invalid arguments");
				return false;
			}
			histo = getHisto(pixels);
			cbox = cboxFromPixels(pixels, histo);
			pq = new PriorityQueue(function(a, b) {
				var va, vb;
				va = a.count();
				vb = b.count();
				if (va > vb) {
					return 1;
				} else if (va < vb) {
					return -1;
				} else {
					return 0;
				}
			});
			pq.push(cbox);
			iter = function(lh, target) {
				var cbox1, cbox2, cboxes, ncolors, niters;
				ncolors = 1;
				niters = 0;
				while (niters < _this.maxIterations) {
					cbox = lh.pop();
					if (!cbox.count()) {
						lh.push(cbox);
						niters++;
						continue;
					}
					cboxes = medianCutApply(histo, cbox);
					cbox1 = cboxes[0];
					cbox2 = cboxes[1];
					if (!cbox1) {
						console.log("cbox1 not defined; shouldn't happen");
						return;
					}
					lh.push(cbox1);
					if (cbox2) {
						lh.push(cbox2);
						ncolors++;
					}
					if (ncolors >= target) {
						return;
					}
					if ((niters++) > _this.maxIterations) {
						console.log("infinite loop; perhaps too few pixels");
						return;
					}
				}
			};
			iter(pq, this.fractByPopulations * maxcolors);
			pq2 = new PriorityQueue(function(a, b) {
				var va, vb;
				va = a.count() * a.volume();
				vb = b.count() * b.volume();
				if (va > vb) {
					return 1;
				} else if (va < vb) {
					return -1;
				} else {
					return 0;
				}
			});
			while (pq.size()) {
				pq2.push(pq.pop());
			}
			iter(pq2, maxcolors - pq2.size());
			cmap = new ColorMap;
			while (pq2.size()) {
				cmap.push(pq2.pop());
			}
			return cmap;
		};

		return MMCQ;

	}).call(this);


	ColorTunes = (function() {
		function ColorTunes() {}

		ColorTunes.getColorMap = function(canvas, sx, sy, w, h, nc, thickness) {
			var index, indexBase, pdata, pixels, x, y, _ref, _ref1;
			if (nc == null) {
				nc = 8;
			}
			pdata = canvas.getContext("2d").getImageData(sx, sy, w, h).data;
			pixels = [];
			for (y = sy, _ref = sy + h; y < _ref; y++) {
				indexBase = y * w * 4;
				for (x = sx, _ref1 = sx + w; x < _ref1; x++) {
					if (thickness == null || (y < (sy+thickness)) || (y > (_ref-thickness)) || (x < (sx+thickness)) || (x > (_ref1-thickness))) {
						index = indexBase + (x * 4);
						pixels.push([pdata[index], pdata[index + 1], pdata[index + 2]]);
					}
				}
			}
			return (new MMCQ()).quantize(pixels, nc);
		};

		ColorTunes.colorDist = function(a, b) {
			var square;
			square = function(n) {
				return n * n;
			};
			return square(a[0] - b[0]) + square(a[1] - b[1]) + square(a[2] - b[2]);
		};

		ColorTunes.fadeout = function(canvas, width, height, opa, color) {
			var idata, idx, pdata, x, y, _i, _j;
			if (opa == null) {
				opa = 0.5;
			}
			if (color == null) {
				color = [0, 0, 0];
			}
			idata = canvas.getContext("2d").getImageData(0, 0, width, height);
			pdata = idata.data;
			for (y = _i = 0; 0 <= height ? _i < height : _i > height; y = 0 <= height ? ++_i : --_i) {
				for (x = _j = 0; 0 <= width ? _j < width : _j > width; x = 0 <= width ? ++_j : --_j) {
					idx = (y * width + x) * 4;
					pdata[idx + 0] = opa * pdata[idx + 0] + (1 - opa) * color[0];
					pdata[idx + 1] = opa * pdata[idx + 1] + (1 - opa) * color[1];
					pdata[idx + 2] = opa * pdata[idx + 2] + (1 - opa) * color[2];
				}
			}
			return canvas.getContext("2d").putImageData(idata, 0, 0);
		};

		ColorTunes.feathering = function(canvas, width, height, size, color) {
			var idata, p, pdata, x, y, x2, y2;
			if (size == null) {
				size = 50;
			}
			if (color == null) {
				color = [0, 0, 0];
			}

			idata = canvas.getContext("2d").getImageData(0, 0, width, height);
			pdata = idata.data;
			function conv (x, y, p) {
				var idx;
				if (p < 0) {
					p = 0;
				}
				if (p > 1) {
					p = 1;
				}
				idx = (y * width + x) * 4;
				pdata[idx + 0] = p * pdata[idx + 0] + (1 - p) * color[0];
				pdata[idx + 1] = p * pdata[idx + 1] + (1 - p) * color[1];
				pdata[idx + 2] = p * pdata[idx + 2] + (1 - p) * color[2];
			}

			for (x = 0; x < width; x++) {
				for (y = 0; y < size; y++) {
					y2 = height-y;
					p = y / size;
					conv(x, y2, p);
				}
			}
			for (y = 0; y < height; y++) {
				for (x = 0; x < size; x++) {
					x2 = width-x;
					p = x / size;
					conv(x2, y, p);
				}
			}
			return canvas.getContext("2d").putImageData(idata, 0, 0);
		};

		ColorTunes.mirror = function(canvas, sy, height, color) {
			var idata, idx, idxu, p, pdata, width, x, y, _i, _j, _ref;
			if (color == null) {
				color = [0, 0, 0];
			}
			width = canvas.width;
			idata = canvas.getContext("2d").getImageData(0, sy - height, width, height * 2);
			pdata = idata.data;
			for (y = _i = height, _ref = height * 2; _i < _ref; y = _i += 1) {
				for (x = _j = 0; _j < width; x = _j += 1) {
					idx = (y * width + x) * 4;
					idxu = ((height * 2 - y) * width + x) * 4;
					p = (y - height) / height + 0.33;
					if (p > 1) {
						p = 1;
					}
					pdata[idx + 0] = (1 - p) * pdata[idxu + 0] + p * color[0];
					pdata[idx + 1] = (1 - p) * pdata[idxu + 1] + p * color[1];
					pdata[idx + 2] = (1 - p) * pdata[idxu + 2] + p * color[2];
					pdata[idx + 3] = 255;
				}
			}
			return canvas.getContext("2d").putImageData(idata, 0, sy - height);
		};

		ColorTunes.launch = function(image, canvas, container) {
			var bgColor, bgColorMap, bgPalette, color, dist, fgColor, fgColor2, fgColorMap, fgPalette, maxDist, rgbToCssString, _i, _j;
			canvas.width = image.width;
			canvas.height = image.height;
			canvas.getContext("2d").drawImage(image, 0, 0, image.width, image.height);
			bgColorMap = ColorTunes.getColorMap(canvas, 0, 0, image.width, image.height, 4, 5);
			bgPalette = bgColorMap.cboxes.map(function(cbox) {
				return {
					count: cbox.cbox.count(),
					rgb: cbox.color
				};
			});
			bgPalette.sort(function(a, b) {
				return b.count - a.count;
			});
			bgColor = bgPalette[0].rgb;
			fgColorMap = ColorTunes.getColorMap(canvas, 0, 0, image.width, image.height, 10, null);
			fgPalette = fgColorMap.cboxes.map(function(cbox) {
				return {
					count: cbox.cbox.count(),
					rgb: cbox.color
				};
			});
			fgPalette.sort(function(a, b) {
				return b.count - a.count;
			});
			maxDist = 0;
			for (_i = 0; _i < fgPalette.length; _i++) {
				color = fgPalette[_i];
				dist = ColorTunes.colorDist(bgColor, color.rgb);
				if (dist > maxDist) {
					maxDist = dist;
					fgColor = color.rgb;
				}
			}

			maxDist = 0;
			for (_j = 0; _j < fgPalette.length; _j++) {
				color = fgPalette[_j];
				dist = ColorTunes.colorDist(bgColor, color.rgb);
				if (dist > maxDist && color.rgb !== fgColor) {
					maxDist = dist;
					fgColor2 = color.rgb;
				}
			}

			ColorTunes.fadeout(canvas, image.width, image.height, 0.8, bgColor);
			ColorTunes.feathering(canvas, image.width, image.height, 10, bgColor);

			rgbToCssString = function(color) {
				return "rgb(" + color[0] + ", " + color[1] + ", " + color[2] + ")";
			};

			container.css({
				"background-color" : rgbToCssString(bgColor),
				"color" : rgbToCssString(fgColor)
			});

			container.find('.preview-indicator').css({
				"background-color" : rgbToCssString(bgColor),
				"display" : "block"
			});
		};

		return ColorTunes;

	})();


	angular.module('RbsChange').directive('rbsImageContainerColorizer', [function () {

		var counter = 0;

		return {
			restrict    : 'A',

			link : function (scope, element, attrs) {
				if (! element.is('img')) {
					throw new Error("Directive 'rbsImageContainerColorizer' can only be applied to <img/> elements.");
				}
				var container = element.closest(attrs.rbsImageContainerColorizer);
				if (container.length) {
					counter++;
					element.hide();
					element.after('<canvas id="rbsImageContainerColorizerCanvas' + counter + '" class="' + element.attr('class') + '"></canvas>');
					var	canvas;
					canvas = $('#rbsImageContainerColorizerCanvas' + counter).get(0);
					element.on('load', function () {
						console.log(element, element.attr("src"), counter);
						// Get image size
						$("<img/>").attr("src", element.attr("src"))
							.load(function() {
								ColorTunes.launch(this, canvas, container);
							});
					});
				}
				else {
					console.warn("Directive 'rbsImageContainerColorizer': container '" + attrs.rbsImageContainerColorizer + "' does not exist.");
				}
			}
		};

	}]);


})(window.jQuery);