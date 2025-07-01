# flagkit-web
styles and images to easily allow the awesome flagkit svgs to be used on the web

## Install
```npm i flagkit-web```

## Get Started

To get started import the ```node_modules/flagkit-web/styles/flagkit.css``` file and copy the ```node_modules/flagkit-web/svgs``` folder into your bundle.


## Usage

To get flag styles you need to add the flag class to your html element, like so:

```<span class="flag"></span>```

To get the flag to appear you need to add the country code in uppercase, like so:

```<span class="flag US"></span>```

### Sizes

By default the flag size is 16x16px however by adding the class ```medium``` you will get 24x24 and ```large``` will up it to 32x32.

Being as these are SVGs you can size them as you see fit. I just added these for convenience.

## Sass

This lib was written using sass and as such the original sass mixins are available via ```node_modules/flagkit-web/styles/flags.scss``` if you would like to create your own .css file.
