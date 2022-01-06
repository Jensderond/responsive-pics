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
(window.wpackioresponsivePicsfocalpointJsonp=window.wpackioresponsivePicsfocalpointJsonp||[]).push([[0],[function(t,i,e){e(1),e(2),t.exports=e(3)},function(t,i,e){var n="responsivePicsdist".replace(/[^a-zA-Z0-9_-]/g,"");e.p=window["__wpackIo".concat(n)]},function(t,i){var e,n,o,a,c,s,d,p;e=jQuery,p={init:function(t){p.wrapper=a,p.picker=s,p.point=c,p.positionFocalPoint(t),p.setEventListeners()},setEventListeners:function(){p.picker.on("click",p.setFocalPoint),"function"==typeof e.ui.draggable&&p.point.draggable({cursor:"move",start:p.startDrag,drag:p.dragging,stop:p.stopDrag,containment:p.wrapper})},positionFocalPoint:function(t){p.x=t.x,p.y=t.y,p.point.css({left:"".concat(t.x,"%"),top:"".concat(t.y,"%")})},setFocalPoint:function(t){d.removeClass("button-disabled");var i=t.offsetY-p.point.height()/2,e=t.offsetX-p.point.width()/2;p.x=Number(e/p.picker.width()*100).toFixed(2),p.y=Number(i/p.picker.height()*100).toFixed(2),p.positionFocalPoint(p)},startDrag:function(t){e("body").addClass("focal-point-dragging"),d.removeClass("button-disabled")},dragging:function(t){p.x=Number(t.target.offsetLeft/p.picker.width()*100).toFixed(2),p.y=Number(t.target.offsetTop/p.picker.height()*100).toFixed(2)},stopDrag:function(t){e("body").removeClass("focal-point-dragging"),p.positionFocalPoint(p)}},e(document).ready((function(){var t=function(){a.css({width:"".concat(o.width(),"px")})},i=function(i){var a=i.get("focalPoint");e(window).on("resize",t),o.on("load",(function(i){t(),p.init(a)})),d.on("click",(function(t){t.preventDefault(),function(t){var i,o,a,c={x:p.x,y:p.y};t.set({focalPoint:c}),e.ajax({url:null===(i=wp)||void 0===i||null===(o=i.ajax)||void 0===o||null===(a=o.settings)||void 0===a?void 0:a.url,method:"POST",data:{action:"save_focal_point",attachment:null==t?void 0:t.attributes}}).done((function(t){n.update()})).fail((function(t,i){console.log("save focal point error",t)})).always((function(){d.addClass("button-disabled")}))}(i)}))},l=function(t){"image"===t.model.get("type")&&(function(t){var i=wp.media.template("attachment-select-focal-point"),e=t.find(".thumbnail, .imgedit-panel-content .imgedit-crop-wrap"),n=e.find("img");console.log(e,n),i&&(e.prepend(i),t.find(".image-focal"),a=t.find(".image-focal__wrapper"),c=t.find(".image-focal__point"),s=t.find(".image-focal__clickarea"),n.prependTo(a),o=a.find(".details-image"));var p=wp.media.template("attachment-save-focal-point"),l=t.find(".attachment-actions, .imgedit-submit");p&&(l.append(p),d=t.find("button.save-attachment-focal-point"))}(t.$el),i(t.model))},r=function(t){console.log("changeView",t);var i=t.model.get("type"),e=t.model.get("focalPoint");"image"===i&&p.positionFocalPoint(e)},f=wp.media.view.Attachment.Details.TwoColumn;f&&(wp.media.view.Attachment.Details.TwoColumn=f.extend({initialize:function(){n=this,this.model.on("change:focalPoint",this.change,this)},render:function(){wp.media.view.Attachment.prototype.render.apply(this,arguments),l(this)},change:function(){r(this)},update:function(){this.views.detach(),this.model.fetch(),this.views.render()}}));var u=wp.media.view.EditImage.Details;u&&(wp.media.view.EditImage.Details=u.extend({initialize:function(t){n=this,this.model.on("change:focalPoint",this.change,this),wp.media.view.EditImage.prototype.initialize.apply(this,arguments)},render:function(){wp.media.view.EditImage.prototype.render.apply(this,arguments),l(this)},back:function(){this.frame.content.mode("edit-metadata")},change:function(){r(this)},update:function(){this.views.detach(),this.model.fetch(),this.views.render()}}))}))},function(t,i,e){}],[[0,1]]]);
//# sourceMappingURL=admin-23d5bc6c.js.map