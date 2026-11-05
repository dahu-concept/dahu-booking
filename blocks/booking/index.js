/**
 * Ubac Booking -- the Gutenberg block.
 * Plain JS using WordPress's global APIs (wp.blocks, wp.element,
 * wp.blockEditor): no JSX and no build step, so it runs exactly as
 * WordPress loads it.
 */
( function ( blocks, element, blockEditor, i18n ) {
    var el = element.createElement;
    var __ = i18n.__;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType( 'ubac-booking/widget', {

        edit: function () {
            var blockProps = useBlockProps( {
                className: 'ubac-booking-block-placeholder',
            } );

            return el(
                'div',
                blockProps,
                el(
                    'div',
                    {
                        style: {
                            border: '1px dashed #a6bbd1',
                            borderRadius: '10px',
                            padding: '28px',
                            textAlign: 'center',
                            background: '#fafbfc',
                            color: '#696c71',
                            fontFamily: 'inherit',
                        },
                    },
                    el(
                        'span',
                        {
                            style: {
                                fontSize: '1.5rem',
                                display: 'block',
                                marginBottom: '8px',
                            },
                        },
                        '📅'
                    ),
                    el(
                        'strong',
                        { style: { display: 'block', marginBottom: '4px' } },
                        __( 'Widget de réservation Ubac Booking', 'ubac-booking' )
                    ),
                    el(
                        'span',
                        { style: { fontSize: '0.9rem' } },
                        __( 'Le calendrier de réservation s\'affichera ici sur le site publié.', 'ubac-booking' )
                    )
                )
            );
        },

        save: function () {
            // The real rendering is left to the PHP render_callback
            // (render.php), so there is nothing to save in the content: this
            // is a dynamic block.
            return null;
        },

    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.i18n
);
