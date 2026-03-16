/**
 * Delice Recipe Manager — Gutenberg Recipe Card Block (v4.0.0)
 *
 * Registers a server-side-rendered block that embeds a recipe card anywhere
 * in the Block Editor.  No build step: uses WordPress globals directly.
 */
( function () {
    'use strict';

    if ( window.deliceBlockRegistered ) { return; }
    window.deliceBlockRegistered = true;

    var el            = wp.element.createElement;
    var registerBlock = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody    = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var ServerSideRender = wp.serverSideRender;
    var Placeholder  = wp.components.Placeholder;
    var Icon         = wp.components.Icon;
    var __           = wp.i18n.__;

    var recipes = ( window.deliceBlockData && window.deliceBlockData.recipes ) || [];

    registerBlock( 'delice-recipe-manager/recipe-card', {
        title:       __( 'Recipe Card', 'delice-recipe-manager' ),
        description: __( 'Embed a recipe card from Delice Recipe Manager.', 'delice-recipe-manager' ),
        icon:        'food',
        category:    'embed',
        keywords:    [ __( 'recipe', 'delice-recipe-manager' ), __( 'food', 'delice-recipe-manager' ), 'delice' ],

        attributes: {
            recipeId: { type: 'integer', default: 0 },
        },

        edit: function ( props ) {
            var recipeId = props.attributes.recipeId;

            function onSelectRecipe( val ) {
                props.setAttributes( { recipeId: parseInt( val, 10 ) || 0 } );
            }

            var inspector = el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: __( 'Recipe Settings', 'delice-recipe-manager' ), initialOpen: true },
                    el( SelectControl, {
                        label:    __( 'Select Recipe', 'delice-recipe-manager' ),
                        value:    recipeId,
                        options:  recipes,
                        onChange: onSelectRecipe,
                    } )
                )
            );

            if ( ! recipeId ) {
                return el(
                    'div',
                    null,
                    inspector,
                    el(
                        Placeholder,
                        {
                            icon:  el( Icon, { icon: 'food' } ),
                            label: __( 'Recipe Card', 'delice-recipe-manager' ),
                        },
                        el( SelectControl, {
                            label:    __( 'Select a recipe to display', 'delice-recipe-manager' ),
                            value:    recipeId,
                            options:  recipes,
                            onChange: onSelectRecipe,
                        } )
                    )
                );
            }

            return el(
                'div',
                { className: 'delice-block-preview-wrap' },
                inspector,
                el( ServerSideRender, {
                    block:      'delice-recipe-manager/recipe-card',
                    attributes: props.attributes,
                } )
            );
        },

        save: function () {
            // Server-side rendered — save returns null.
            return null;
        },
    } );
}() );
