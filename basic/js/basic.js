/**
 * Demo Website of FOXEL SA, Geneva Switzerland.
 *
 * Copyright (c) 2014 FOXEL SA - http://foxel.ch
 * Please read <http://foxel.ch/license> for more information.
 *
 *
 * Author(s):
 *
 *      Alexandre Kraft <a.kraft@foxel.ch>
 *
 *
 * This file is part of the FOXEL project <http://foxel.ch>.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * Additional Terms:
 *
 *      You are required to preserve legal notices and author attributions in
 *      that material or in the Appropriate Legal Notices displayed by works
 *      containing it.
 *
 *      You are required to attribute the work as explained in the "Usage and
 *      Attribution" section of <http://foxel.ch/license>.
 */

$(document).ready(function() {

    var panorama;

    /**
     * layout
     */
    var _layout = function() {

        $('#wrapper').width($(window).width());
        $('#wrapper').height($(window).height());

    };

    // layout
    _layout();
    $(window).on('resize', function(e) {
        _layout();
    });

    // navigation interaction
    var navInteraction = false;

    /**
     * init nav scrollbar
     */
    var _scrollbar = function() {

        var _w = 0;
        $.each($('#nav .dataset'),function(index,el) {
            _w += $(el).outerWidth(true);
        });
        $('#nav .area').width(_w+10);

        $('#nav .scrollable').mCustomScrollbar({
            axis: 'x',
            theme: 'light-thin',
            advanced: {
                updateOnContentResize: true
            }
        });

        // navigation open by default
        _navigation_open();

        // navigation close automatically after 5 seconds if no interaction
        setTimeout(function() {
            if (!navInteraction)
                _navigation_close();
        },5000);

    };

    /**
     * panorama currently moving
     */
    var _isPanoMoving = function() {
        if (panorama == null)
            return false;
        return !(panorama.mode.mousedown=='undefined' || !panorama.mode.mousedown);
    };

    /**
     * panorama navigation open
     */
    var _navigation_open = function() {

        var navTargetHeight = $('#nav .main').outerHeight(true);

        $('#nav .main').addClass('active');
        $('#nav .tab .act').addClass('active');
        $('#nav .tab .act .txt').html('Close');

        $('#nav .shade').stop(true,false).animate({
            height: navTargetHeight,
            opacity: 0.85
        },400,'easeOutQuart');

        $('#nav .tab').stop(true,false).animate({
            top: navTargetHeight
        },400,'easeOutQuart');

        $('#nav .main').stop(true,false).animate({
            bottom: -navTargetHeight
        },400,'easeOutQuart');

        $('#nav .tab .lay').stop(true,false).fadeTo(400,0.85);

    };

    /**
     * panorama navigation close
     */
    var _navigation_close = function() {

        $('#nav .main').removeClass('active');
        $('#nav .tab .act').removeClass('active');
        $('#nav .tab .act .txt').html('More Demos');

        $('#nav .shade').stop(true,false).animate({
            height: navStartHeight,
            opacity: 0.5
        },400,'easeOutQuart');

        $('#nav .tab').stop(true,false).animate({
            top: navStartHeight
        },400,'easeOutQuart');

        $('#nav .main').stop(true,false).animate({
            bottom: navStartHeight
        },400,'easeOutQuart');

        $('#nav .tab .lay').stop(true,false).fadeTo(400,0.5);

    };

    // nav scrollbar
    _scrollbar();

    // nav
    var navStartHeight = 0;

    /**
     * nav click
     */
    $('#nav > .tab > .act').on('click',function() {
        $('#nav > .main').hasClass('active') ? _navigation_close() : _navigation_open();
    });

    /**
     * nav main mouseenter
     */
    $('#nav > .main').on('mouseenter',function() {
        navInteraction = true;
    });

    /**
     * nav main mouseleave
     */
    $('#nav > .main').on('mouseleave',function() {
        navInteraction = false;
    });

    // footer
    var footerStartHeight = $('footer .shade').height();

    /**
     * footer mouseenter
     */
    $('footer').on('mouseenter',function() {

        if (_isPanoMoving())
            return;

        var morePosition = $('footer .more').position().top;
        var footerTargetHeight = $('footer .more').outerHeight(true) + morePosition;

        $('footer .shade').stop(true,false).animate({
            height: footerTargetHeight
        },400,'easeOutQuart');

        $('footer .main').stop(true,false).animate({
            height: (footerTargetHeight+Math.round(morePosition/4))
        },400,'easeOutQuart');

        $('footer .logo.attribution').stop(true,false).fadeOut(250);
        $('footer .caption').stop(true,false).fadeOut(250);

    });

    /**
     * footer mouseleave
     */
    $('footer').on('mouseleave',function() {

        if (_isPanoMoving())
            return;

        $('footer .shade').stop(true,false).animate({
            height: footerStartHeight
        },400,'easeOutQuart');

        $('footer .main').stop(true,false).animate({
            height: footerStartHeight
        },400,'easeOutQuart');

        $('footer .logo.attribution').stop(true,false).delay(250).fadeIn(250);
        $('footer .caption').stop(true,false).delay(250).fadeIn(250);

    });

    /**
     * arrow move
     */
    var _arrow = function() {

        $('footer .scroll > div')
            .delay(1000)
            .animate({
                    marginTop: '+=5',
                },250,'swing')
            .animate({
                    marginTop: '-=5',
                    opacity: 0.75
                }, {
                    duration: 250,
                    complete: function() {
                        _arrow();
                    },
                    easing: 'swing'
                });

    };

    // arrow
    _arrow();

    // options
    var options = {

        rotation: {
            heading: -90,
            tilt: 0,
            roll: 0
        },

        camera: {
            zoom: {
                current: 1.5
            }
        },

        fov: {
            max: 140
        },

        controls: {
            touch: {
                move: {
                    active: true
                },
                zoom: {
                    active: true
                }
            },
            keyboard: {
                move: {
                    active: true,
                    step: 2
                },
                zoom: {
                    active: true
                }
            },
            devicemotion: {
                move: {
                    active: false
                }
            }
        },

        renderer: {
            precision: 'lowp',
            antialias: false,
            alpha: false
        },

        list: {
            images: {},
            defaults: {
                columns: 16,
                rows: 8,
                dirName: cfg.path
            }
        }

    };

    // current image
    options.list.images[cfg.src] = {};

    // override options
    $.extend(options,cfg.override);

    // freepano
    $('#pano').panorama(options);
    panorama = $('#pano').data('pano');

    $(document).on('keypress',function(e){
        switch(e.keyCode) {
            case 32:
                console.log('lon ['+panorama.lon+'] lat ['+panorama.lat+'] tilt ['+panorama.rotation.tilt+'] roll ['+panorama.rotation.roll+']');
                break;
        }
    });

});
