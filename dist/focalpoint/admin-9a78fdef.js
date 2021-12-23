/*!
 * 
 * ResponsivePics
 * 
 * @author Booreiland
 * @version 1.4.0
 * @link https://responsive.pics
 * @license undefined
 * 
 * Copyright (c) 2021 Booreiland
 * 
 * This software is released under the [MIT License](https://github.com/booreiland/responsive-pics/blob/master/LICENSE)
 */
(window.wpackioresponsivePicsfocalpointJsonp=window.wpackioresponsivePicsfocalpointJsonp||[]).push([[0],[function(t,e,a){a(1),a(2),t.exports=a(3)},function(t,e,a){var n="responsivePicsdist".replace(/[^a-zA-Z0-9_-]/g,"");a.p=window["__wpackIo".concat(n)]},function(t,e){var a;(a=jQuery)(document).ready((function(){var t=function(t){var e=wp.media.template("attachment-select-focal-point"),a=t.find(".thumbnail"),n=t.find(".details-image");e&&(a.prepend(e),n.prependTo(t.find(".image-focal__wrapper")));var o=wp.media.template("attachment-save-focal-point"),i=t.find(".attachment-actions");o&&i.append(o)},e=function(t){var e=t.id;console.log("initFocalPoint: ".concat(e),t)},n=wp.media.view.Attachment.Details.TwoColumn;wp.media.view.Attachment.Details.TwoColumn=n.extend({initialize:function(){this.model.on("change:compat",this.change,this)},render:function(){wp.media.view.Attachment.prototype.render.apply(this,arguments);var a=this.model.attributes.type;return"image"===a&&(t(this.$el),e(this.model)),this},change:function(){var t=this.model.get("compat");console.log("change",a(t["attachment-fields"])),"image"===this.model.attributes.type&&e(this.model)}})})),function(t,e,a){function n(t,e,a){var n=t;return t<e?n=e:t>a&&(n=a),n}var o="image-focal",i="image-focal__img",c="image-focal__point",s="image-focal__clickarea",f="image-focal__button",u="button-primary",r="button-disabled";t.responsivePics=t.responsivePics||{},t.responsivePics.focalPoint=function(l,h,d){var m,p,v=this;v.$el=t(l),v.el=l,v.$el.data("responsivePics.focalPoint",v),v.init=function(){v.options=t.extend({},t.responsivePics.focalPoint.defaultOptions,d),v.addInterfaceElements(),v.attachment.init(),v.focusInterface.init(),v.saveButton.init(),t(a).on("resize",v.attachment.updateDimensionData)},v.addInterfaceElements=function(){m=t(".".concat(o)),p=t(".".concat(s))},v.attachment={$el:!1,id:!1,width:!1,height:!1,offset:{x:!1,y:!1},focalPoint:{x:50,y:50},init:function(){console.log("init",h),v.attachment.$el=t(".".concat(i)),v.attachment.id=h,v.attachment.getData(),v.attachment.$el.on("load",(function(){v.attachment.updateDimensionData()}))},getData:function(){var e={id:v.attachment.id};t.ajax({type:"POST",url:ajaxurl,data:{action:"get_focal_point",attachment:e},dataType:"json"}).always((function(t){if(!0===t.success&&t.data&&t.data.focal_point)try{if(void 0===t.data.focal_point.x||void 0===t.data.focal_point.y)throw new Error("no coordinates");v.attachment.focalPoint=t.data.focal_point}catch(t){console.log(t)}v.attachment.updateDimensionData(),v.focusInterface.updateStylePosition(),v.focusInterface.$el.css({display:"block"}),v.focusInterface.updateDimensionData(),v.focusInterface.updateStyleBackground()}))},updateDimensionData:function(){var t=v.attachment.$el;v.attachment.width=t.width(),v.attachment.height=t.height(),v.attachment.offset.x=t.offset().left,v.attachment.offset.y=t.offset().top}},v.focusInterface={$el:!1,width:0,height:0,radius:0,offset:{x:0,y:0},position:{x:0,y:0},clickPosition:{x:0,y:0},state:{move:!1,active:!1,hover:!1},init:function(){v.focusInterface.$el=t(".".concat(c)),p.on("mousedown",(function(t){1===t.which&&v.focusInterface.startMove(t,!0).move(t)})),v.focusInterface.$el.on("mousedown",(function(t){1===t.which&&v.focusInterface.startMove(t)})).on("mouseenter",(function(){v.focusInterface.hover(!0)})).on("mouseleave",(function(){v.focusInterface.hover(!1)})),t(e).on("mouseup",(function(t){1===t.which&&(v.focusInterface.state.move=!1,v.focusInterface.state.active=!1,m.removeClass("is-active"))})).on("mousemove",(function(t){v.focusInterface.move(t)})).on("resize",(function(){v.focusInterface.updateDimensionData().updateStyle()}))},startMove:function(t,e){return v.attachment.updateDimensionData(),v.focusInterface.updateDimensionData().updateClickPosition(t,e),v.saveButton.highlight(),m.addClass("is-active"),v.focusInterface.state.move=!0,v.focusInterface.state.active=!0,this},move:function(t){if(!1===v.focusInterface.state.move)return!1;var e={},a=v.attachment.offset,o=v.focusInterface.clickPosition;return e.x=t.pageX-a.x-o.x,e.y=t.pageY-a.y-o.y,e.x=n(e.x,0,v.attachment.width),e.y=n(e.y,0,v.attachment.height),v.attachment.focalPoint={x:e.x/v.attachment.width*100,y:e.y/v.attachment.height*100},v.focusInterface.position=e,v.focusInterface.updateStyle(),this},updateStyle:function(){return v.focusInterface.updateStylePosition(),v.focusInterface.updateStyleBackground(),this},updateStylePosition:function(){return v.focusInterface.$el.css({left:"".concat(v.attachment.focalPoint.x,"%"),top:"".concat(v.attachment.focalPoint.y,"%")}),this},updateStyleBackground:function(){var t=0-(v.focusInterface.position.x-v.focusInterface.radius),e=0-(v.focusInterface.position.y-v.focusInterface.radius);return v.focusInterface.$el.css({backgroundImage:'url("'.concat(v.attachment.$el.attr("src"),'")'),backgroundSize:"".concat(v.attachment.width,"px ").concat(v.attachment.height,"px"),backgroundPosition:"".concat(t,"px ").concat(e,"px ")}),this},updateClickPosition:function(t,e){var a={x:0,y:0};if(!0!==e){var n=v.focusInterface.offset;a.x=t.pageX-n.x,a.y=t.pageY-n.y}return v.focusInterface.clickPosition=a,this},updateDimensionData:function(){v.focusInterface.width=v.focusInterface.$el.width(),v.focusInterface.height=v.focusInterface.$el.height();var t=v.focusInterface.width/2;v.focusInterface.radius=t;var e=v.focusInterface.$el.offset();return v.focusInterface.offset={x:e.left+t,y:e.top+t},v.focusInterface.position={x:v.attachment.focalPoint.x/100*v.attachment.width,y:v.attachment.focalPoint.y/100*v.attachment.height},this},hover:function(t){v.focusInterface.state.hover=t,m.toggleClass("is-hover",t)}},v.saveButton={$el:!1,isSaving:!1,init:function(){v.saveButton.$el=t(".".concat(f)),v.saveButton.$el.on("click",v.sendImageCropDataByAjax)},highlight:function(){v.saveButton.$el.removeClass(r).addClass(u).text(responsivePicsL10n.saveButton)},activate:function(){v.saveButton.$el.removeClass(r).removeClass(u)},disable:function(){v.saveButton.$el.removeClass(u).addClass(r)}},v.sendImageCropDataByAjax=function(){var e={id:v.attachment.id,focal_point:v.attachment.focalPoint};t.ajax({type:"POST",url:ajaxurl,data:{action:"set_focal_point",attachment:e},dataType:"json",beforeSend:function(){return!0!==v.saveButton.isSaving&&(v.saveButton.$el.text(responsivePicsL10n.saving),v.saveButton.disable(),v.saveButton.isSaving=!0,!0)}}).always((function(t){var e=responsivePicsL10n.saved;!0!==t.success&&(v.saveButton.activate(),e=responsivePicsL10n.tryAgain),v.saveButton.$el.text(e),v.saveButton.isSaving=!1}))}},t.responsivePics.focalPoint.defaultOptions={myDefaultValue:""},t.fn.initFocalPoint=function(e){return console.log("initFocalPoint",this,parseInt(e)),this.each((function(){new t.responsivePics.focalPoint(this,parseInt(e)).init()}))},t.responsivePics.focalPoint.initAttachment=function(e){var a=t(".attachment-details, .image-editor");if(a.find(".details-image, .imgedit-crop-wrap img").length&&!t(".image-focal").length)return a.initFocalPoint(e)}}(jQuery,window,document)},function(t,e,a){}],[[0,1]]]);
//# sourceMappingURL=admin-9a78fdef.js.map