/*!
 * 
 * ResponsivePics
 * 
 * @author Booreiland
 * @version 1.4.0
 * @link https://responsive.pics
 * @license undefined
 * 
 * Copyright (c) 2022 Booreiland
 * 
 * This software is released under the [MIT License](https://github.com/booreiland/responsive-pics/blob/master/LICENSE)
 */
(window.wpackioresponsivePicsfocalpointJsonp=window.wpackioresponsivePicsfocalpointJsonp||[]).push([[0],[function(t,o,i){i(1),i(2),t.exports=i(3)},function(t,o,i){var n="responsivePicsdist".replace(/[^a-zA-Z0-9_-]/g,"");i.p=window["__wpackIo".concat(n)]},function(t,o){var i,n,e,a,c,s,p;i=jQuery,p={init:function(t){p.wrapper=e,p.picker=c,p.point=a,p.positionFocalPoint(t),p.setEventListeners()},setEventListeners:function(){p.picker.on("click",p.setFocalPoint),"function"==typeof i.ui.draggable&&p.point.draggable({cursor:"move",start:p.startDrag,drag:p.dragging,stop:p.stopDrag,containment:p.wrapper})},positionFocalPoint:function(t){p.x=t.x,p.y=t.y,p.point.css({left:"".concat(t.x,"%"),top:"".concat(t.y,"%")})},setFocalPoint:function(t){s.removeClass("button-disabled");var o=t.offsetY-p.point.height()/2,i=t.offsetX-p.point.width()/2;p.x=Number(i/p.picker.width()*100).toFixed(2),p.y=Number(o/p.picker.height()*100).toFixed(2),p.positionFocalPoint(p)},startDrag:function(t){i("body").addClass("focal-point-dragging"),s.removeClass("button-disabled")},dragging:function(t){p.x=Number(t.target.offsetLeft/p.picker.width()*100).toFixed(2),p.y=Number(t.target.offsetTop/p.picker.height()*100).toFixed(2)},stopDrag:function(t){i("body").removeClass("focal-point-dragging"),p.positionFocalPoint(p)}},i(document).ready((function(){var t=function(t){var o=wp.media.template("attachment-select-focal-point"),i=t.find(".thumbnail"),p=t.find(".details-image");o&&(i.prepend(o),t.find(".image-focal"),e=t.find(".image-focal__wrapper"),a=t.find(".image-focal__point"),c=t.find(".image-focal__clickarea"),p.prependTo(e),n=e.find(".details-image"));var r=wp.media.template("attachment-save-focal-point"),l=t.find(".attachment-actions");r&&(l.append(r),s=t.find("button.save-attachment-focal-point"))},o=function(){e.css({width:"".concat(n.width(),"px")})},r=function(t){var e=t.get("focalPoint");i(window).on("resize",o),n.on("load",(function(t){o(),p.init(e)})),s.on("click",(function(o){o.preventDefault(),function(t){var o={x:p.x,y:p.y};console.log(wp.ajax.settings.url),i.ajax({type:"POST",url:wp.ajax.settings.url,data:{action:"save_focal_point",attachment:t,focalPoint:o},dataType:"json"}).always((function(t){console.log(t),s.addClass("button-disabled")})).success((function(i){t.set({focalPoint:o})})).error((function(t){console.log(t)}))}(t)}))},l=wp.media.view.Attachment.Details.TwoColumn;wp.media.view.Attachment.Details.TwoColumn=l.extend({initialize:function(){this.model.on("change:focalPoint",this.change,this)},render:function(){wp.media.view.Attachment.prototype.render.apply(this,arguments);var o=this.model.get("type");return"image"===o&&(t(this.$el),r(this.model)),this},change:function(){var t=this.model.get("type"),o=this.model.get("focalPoint");console.log("change:focalPoint",o),"image"===t&&p.positionFocalPoint(o)}})}))},function(t,o,i){}],[[0,1]]]);
//# sourceMappingURL=admin-207a076a.js.map