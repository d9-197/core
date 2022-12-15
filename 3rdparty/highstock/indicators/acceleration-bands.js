/*
 Highstock JS v10.3.2 (2022-11-28)

 Indicator series type for Highcharts Stock

 (c) 2010-2021 Daniel Studencki

 License: www.highcharts.com/license
*/
(function(a){"object"===typeof module&&module.exports?(a["default"]=a,module.exports=a):"function"===typeof define&&define.amd?define("highcharts/indicators/acceleration-bands",["highcharts","highcharts/modules/stock"],function(g){a(g);a.Highcharts=g;return a}):a("undefined"!==typeof Highcharts?Highcharts:void 0)})(function(a){function g(a,d,f,g){a.hasOwnProperty(d)||(a[d]=g.apply(null,f),"function"===typeof CustomEvent&&window.dispatchEvent(new CustomEvent("HighchartsModuleLoaded",{detail:{path:d,
module:a[d]}})))}a=a?a._modules:{};g(a,"Stock/Indicators/MultipleLinesComposition.js",[a["Core/Series/SeriesRegistry.js"],a["Core/Utilities.js"]],function(a,d){var f=a.seriesTypes.sma.prototype,g=d.defined,n=d.error,q=d.merge,h;(function(a){function u(b){return"plot"+b.charAt(0).toUpperCase()+b.slice(1)}function y(b,p){var a=[];(b.pointArrayMap||[]).forEach(function(b){b!==p&&a.push(u(b))});return a}function c(){var b=this,p=b.linesApiNames,a=b.areaLinesNames,e=b.points,c=b.options,x=b.graph,t={options:{gapSize:c.gapSize}},
k=[],d=y(b,b.pointValKey),h=e.length,w;d.forEach(function(b,a){for(k[a]=[];h--;)w=e[h],k[a].push({x:w.x,plotX:w.plotX,plotY:w[b],isNull:!g(w[b])});h=e.length});if(b.userOptions.fillColor&&a.length){var m=d.indexOf(u(a[0]));m=k[m];a=1===a.length?e:k[d.indexOf(u(a[1]))];d=b.color;b.points=a;b.nextPoints=m;b.color=b.userOptions.fillColor;b.options=q(e,t);b.graph=b.area;b.fillGraph=!0;f.drawGraph.call(b);b.area=b.graph;delete b.nextPoints;delete b.fillGraph;b.color=d}p.forEach(function(a,e){k[e]?(b.points=
k[e],c[a]?b.options=q(c[a].styles,t):n('Error: "There is no '+a+' in DOCS options declared. Check if linesApiNames are consistent with your DOCS line names."'),b.graph=b["graph"+a],f.drawGraph.call(b),b["graph"+a]=b.graph):n('Error: "'+a+" doesn't have equivalent in pointArrayMap. To many elements in linesApiNames relative to pointArrayMap.\"")});b.points=e;b.options=c;b.graph=x;f.drawGraph.call(b)}function t(b){var a,c=[];b=b||this.points;if(this.fillGraph&&this.nextPoints){if((a=f.getGraphPath.call(this,
this.nextPoints))&&a.length){a[0][0]="L";c=f.getGraphPath.call(this,b);a=a.slice(0,c.length);for(var e=a.length-1;0<=e;e--)c.push(a[e])}}else c=f.getGraphPath.apply(this,arguments);return c}function x(b){var a=[];(this.pointArrayMap||[]).forEach(function(c){a.push(b[c])});return a}function d(){var b=this,a=this.pointArrayMap,c=[],e;c=y(this);f.translate.apply(this,arguments);this.points.forEach(function(d){a.forEach(function(a,p){e=d[a];b.dataModify&&(e=b.dataModify.modifyValue(e));null!==e&&(d[c[p]]=
b.yAxis.toPixels(e,!0))})})}var h=[],m=["bottomLine"],A=["top","bottom"],B=["top"];a.compose=function(b){if(-1===h.indexOf(b)){h.push(b);var a=b.prototype;a.linesApiNames=a.linesApiNames||m.slice();a.pointArrayMap=a.pointArrayMap||A.slice();a.pointValKey=a.pointValKey||"top";a.areaLinesNames=a.areaLinesNames||B.slice();a.drawGraph=c;a.getGraphPath=t;a.toYData=x;a.translate=d}return b}})(h||(h={}));return h});g(a,"Stock/Indicators/ABands/ABandsIndicator.js",[a["Stock/Indicators/MultipleLinesComposition.js"],
a["Core/Series/SeriesRegistry.js"],a["Core/Utilities.js"]],function(a,d,f){var g=this&&this.__extends||function(){var a=function(d,c){a=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(a,c){a.__proto__=c}||function(a,c){for(var d in c)c.hasOwnProperty(d)&&(a[d]=c[d])};return a(d,c)};return function(d,c){function f(){this.constructor=d}a(d,c);d.prototype=null===c?Object.create(c):(f.prototype=c.prototype,new f)}}(),n=d.seriesTypes.sma,q=f.correctFloat,h=f.extend,z=f.merge;f=function(a){function d(){var c=
null!==a&&a.apply(this,arguments)||this;c.data=void 0;c.options=void 0;c.points=void 0;return c}g(d,a);d.prototype.getValues=function(c,d){var f=d.period,g=d.factor;d=d.index;var h=c.xData,m=(c=c.yData)?c.length:0,n=[],t=[],b=[],p=[],u=[],e;if(!(m<f)){for(e=0;e<=m;e++){if(e<m){var l=c[e][2];var r=c[e][1];var v=g;l=q(r-l)/(q(r+l)/2)*1E3*v;n.push(c[e][1]*q(1+2*l));t.push(c[e][2]*q(1-2*l))}if(e>=f){l=h.slice(e-f,e);var k=c.slice(e-f,e);v=a.prototype.getValues.call(this,{xData:l,yData:n.slice(e-f,e)},
{period:f});r=a.prototype.getValues.call(this,{xData:l,yData:t.slice(e-f,e)},{period:f});k=a.prototype.getValues.call(this,{xData:l,yData:k},{period:f,index:d});l=k.xData[0];v=v.yData[0];r=r.yData[0];k=k.yData[0];b.push([l,v,k,r]);p.push(l);u.push([v,k,r])}}return{values:b,xData:p,yData:u}}};d.defaultOptions=z(n.defaultOptions,{params:{period:20,factor:.001,index:3},lineWidth:1,topLine:{styles:{lineWidth:1}},bottomLine:{styles:{lineWidth:1}},dataGrouping:{approximation:"averages"}});return d}(n);
h(f.prototype,{areaLinesNames:["top","bottom"],linesApiNames:["topLine","bottomLine"],nameBase:"Acceleration Bands",nameComponents:["period","factor"],pointArrayMap:["top","middle","bottom"],pointValKey:"middle"});a.compose(f);d.registerSeriesType("abands",f);"";return f});g(a,"masters/indicators/acceleration-bands.src.js",[],function(){})});
//# sourceMappingURL=acceleration-bands.js.map