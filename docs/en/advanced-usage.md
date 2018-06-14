# Advanced Usage

## Use with HTML Sketchapp

This module is designed to be used with the [HTML Sketchapp CLI](https://github.com/seek-oss/html-sketchapp-cli)

**html-sketchapp.config.js**
```
module.exports = {
	url: your_base_url_here+'/_components/all',
	outDir: 'public/asketch',
	viewports: {
		Desktop: '1024x768',
		Mobile: '320x568'
	},
}
```
