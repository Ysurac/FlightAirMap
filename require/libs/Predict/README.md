# Overview

Predict is a partial PHP port of the [Gpredict](http://gpredict.oz9aec.net/) program
that allows real-time tracking and orbit prediction of satellites from two line
element sets.  It supports the SGP4 and SDP4 [models](http://en.wikipedia.org/wiki/Simplified_perturbations_models) for prediction.

# Installation

Just clone this repo and try out the tests and examples from the root of the checkout.

# Examples/Tests

The tests directory includes a port of the original sgpsdp test files from
Gpredict.  They are pretty close.

Included in the examples directory is a sample iss.tle (with an update script, which you
should run first).  There are two examples, the visible_passes.php script and the benchmark.php
script.  The former is for generating visible pass predictions of the ISS, and its output is
similar to what you might get from the Heavens-Above website, and it is heavily commented.
The latter just does predictions for benchmarking with xhprof.

You can see an image of a Predict/Google Maps API mash-up I did for fun:

![Google Maps Mashup](https://raw.github.com/shupp/Predict/master/examples/google_maps_iss.png)

You can also see an example visible pass plotted from the polar view of the observer:

![Google Maps Mashup](https://raw.github.com/shupp/Predict/master/examples/pass_polar_plot.png)

# About this port

This port largely maintains the style and organization of the original C code, but
scopes methods into classes rather than leaving everything in the global scope.
The motivation for this is so that changes upstream can more easily be integrated over
time.  Only the prediction routines have been ported.
