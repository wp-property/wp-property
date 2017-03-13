s.isGrid = function(){
    if(!s.params.lightBox && (s.params.sliderType == "12grid" || s.params.sliderType == "12mosaic"))
        return true;
}
s.is12grid = function(){
    if(!s.params.lightBox && s.params.sliderType == "12grid")
        return true;
}
s.is12mosaic = function(){
    if(!s.params.lightBox && s.params.sliderType == "12mosaic")
        return true;
}
s.isLightbox = function(){
    if(s.params.lightBox)
        return true;
}

s.setSlideSize_12mosaic = function(slide, s){
    if( typeof slide == 'undefined' ) {
        return 0;
    }
    
    var width, height;
    var slide = jQuery(slide);
    var img = slide.find('img');
    var maxHeight = s.container.height() / s.params.slidesPerColumn;

    if( typeof img[0] == 'undefined' ) {
        return 0;
    }

    var attrWidth  = parseInt(img.attr('width'));
    var attrHeight  = parseInt(img.attr('height'));
    var ratio   = attrWidth / attrHeight;

    if(slide.is(':first-child')){
        height = s.container.height();
    }
    else{
        height = maxHeight;
    }
    width   = height * ratio;
    img.width(width)
         .height(height);
    slide.width(width)
         .height(height);
    img[0].style.setProperty('width', width + "px", 'important');
    img[0].style.setProperty('height', height + "px", 'important');
    return width;
}

s.setSlideSize_12grid = function(slide, s){
    if( typeof slide == 'undefined' ) {
        return 0;
    }
    
    var width, height;
    var top = 0, left = 0;
    var slideWidth, slideHeight, slideRatio;
    var aspectWidth, aspectHeight;
    var slide = jQuery(slide);
    var img = slide.find('img');
    var maxHeight = (s.container.height() - s.params.spaceBetween) / s.params.slidesPerColumn;

    if( typeof img[0] == 'undefined' ) {
        return 0;
    }

    var attrWidth  = parseInt(img.attr('width'));
    var attrHeight  = parseInt(img.attr('height'));
    var imgRatio   = attrWidth / attrHeight;

    slideHeight = maxHeight;
    slideWidth   = slideHeight;

    width   = attrWidth;
    height  = attrHeight;

    if(slide.is(':first-child')){
        slideHeight = s.container.height();
        slideRatio = attrWidth / attrHeight;
        slideWidth = slideHeight * imgRatio;
        width = slideWidth + 'px';
        height = slideHeight + 'px';
    }
    else if(1 > imgRatio){ // Here 1 is slide ratio. Because slide width and height is same.
        width = "100%";
        height = "auto";
        aspectHeight = slideWidth / imgRatio;
        top = (slideHeight - aspectHeight) / 2;
    }
    else{
        width = "auto";
        height = "100%";
        aspectWidth = slideHeight * imgRatio;
        left = (slideWidth - aspectWidth) / 2;
    }
    img.css({
        width: width,
        height: height,
        position: 'relative',
        left: left + "px",
        top: top + "px"
    });
    img[0].style.setProperty('width', width , 'important');
    img[0].style.setProperty('height', height , 'important');

    slide.height(slideHeight);
    
    return slideWidth;
}
