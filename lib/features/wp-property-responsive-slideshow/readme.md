### WP-Property: Responsive Slideshow Plugin

Responsive Slideshow for WP-Property plugin

***
[![Issues - Bug](https://badge.waffle.io/wp-property/wp-property-responsive-slideshow.png?label=bug&title=Bugs)](http://waffle.io/wp-property/wp-property-responsive-slideshow)
[![Issues - Backlog](https://badge.waffle.io/wp-property/wp-property-responsive-slideshow.png?label=backlog&title=Backlog)](http://waffle.io/wp-property/wp-property-responsive-slideshow/)
[![Issues - Active](https://badge.waffle.io/wp-property/wp-property-responsive-slideshow.png?label=in progress&title=Active)](http://waffle.io/wp-property/wp-property-responsive-slideshow/)
***

## Requirements
This Required install wp property installed**.

This plugin contains a ```composer.json``` file for those of you who manage your PHP dependencies with [Composer](https://getcomposer.org).

## Shortcode Reference
Shortcode: `[property_responsive_slideshow]`
# Usage

#### parameters
Parameter                                 | Name                  | Short Description                             | Values                                | Default
---                                       | ---                   | ---                                           | ---                                   | ---
[property_id](#property_id)               | Property ID           | ID of property to show.                       | Property ID                           | Current property
[slideshow_layout](#slideshow_layout)     | Slideshow layout      | Slideshow layout                              | auto, strict, fullwidth               | auto
[slideshow_type](#slideshow_type)         | Slideshow Types       | Type of slideshow                             | standard, thumbnailCarousel           | thumbnailCarousel
[slider_type](#slider_type)               | Slider Type           | Type of slider                                | standard, carousel, 12grid, 12mosaic  |  standard
[grid_image_size](#grid_image_size)     | Image size for grid   | Image size for 12grid and 12mosaic.           | Image sizes							  |  medium
[slider_width](#slider_width)             | Slider Width          | Set width of the slideshow                    | in px or %                            | none
[slider_auto_height](#slider_auto_height) | Slider Auto Height    | Automatic height based on image size.         | true, false                           | false
[slider_height](#slider_height)           | Slider Height         | Set Slider height                             | in px or %                            | 16:9
[slider_min_height](#slider_min_height)   | Slider Minimum Height | Minimum slider height.                        | in px or %                            | none
[slider_max_height](#slider_max_height)   | Slider Maximum Height | Maximum slider height.                        | in px or %                            | none
[lb_title_1](#lb_title_1)                 | Lightbox Title line 1 | Lightbox Title line 1. Select any attribute.  | attributes                            | none
[lb_title_2](#lb_title_2)                 | Lightbox Title line 2 | Lightbox Title line 2. Select any attribute.  | attributes                            | none


#### property_id
If not empty, Slideshow will show for particular property, which ID is set. If not provided will show slideshow for current property.

#### slideshow_layout
Options           | Name                          | Description                            
---               | ---                           | ---                                    
auto              | Responsive                    | Responsive slideshow. It take width of container and get height from height parameter.
strict            | Strict                        | Apply the exact width and height of the slider based on the slider_width and slider_height parameters.
fullwidth         | Full Width                    | Force the width of the slider to always fill the full browser width. The height will still be calculated via slider_height or via slider_auto_height if enabled.

#### slideshow_type
Options           | Name                          | Description
---               | ---                           | ---
standard          | Standard Slideshow            | Slideshow without thumbnail carousel.  
thumbnailCarousel | Thumbnail Carousel Slideshow  | Slideshow with thumbnail carousel.     

#### slider_type
Options           | Name                          | Description
---               | ---                           | ---
standard          | Standard Slider               | The Standard Slider shows one slide at a time.
carousel          | Carousel Slider               | It shows previews of the previous and next images. If have space.
12grid            | 1:2 Grid Slider               | One large image followed by stacked smaller images.
12mosaic          | 1:2 Mosaic Slider             | Same as 1:2 Grid Slider but slide with be automatic based on aspect ration.

#### grid_image_size
##### Image size for grid
Image size for 12grid and 12mosaic. Will not apply on first image.

#### slider_width
##### Slider Width
Set width of the slideshow. 
Dependency Only work with strict slideshow layout.

#### slider_auto_height
##### Slider Auto Height
Allows the height of the carousel to adjust based on the image size. If the image size is short or tall, the carousel will rollup/rolldown to new height.
Dependency Not work with grid slider type. Ex. 1:2 Grid Slider or 1:2 Mosaic Slider

#### slider_height
##### Slider Height
Sets the height of the slider container.
Ignore if slider_auto_height parameter is set. When slideshow layout is not strict slider_width and slider_height is use to calculate ratio and use slider width as base to calculate height.

#### slider_min_height
##### Slider Minimum Height
Sets the minimum height of the slider.

#### slider_max_height
##### Slider Maximum Height
Sets the maximum height of the slider.

#### lb_title_1
##### Lightbox Title line 1
You can provide one or more attribute to show on lightbox title on line no 1. You need to separate with comma(,) if you provide more than one attribute. You can find attributes on Developer tab on setting page of Property.

#### lb_title_2
##### Lightbox Title line 2
You can provide one or more attribute to show on lightbox title on line no 2. You need to separate with comma(,) if you provide more than one attribute. You can find attributes on Developer tab on setting page of Property.
