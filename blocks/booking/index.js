/**
 * Dahu Booking — Bloc Gutenberg
 * Écrit en JS vanilla avec les API WordPress globales (wp.blocks, wp.element, wp.blockEditor),
 * pas de JSX ni d'étape de build : fonctionne directement tel que chargé par WordPress.
 */
( function ( blocks, element, blockEditor, i18n ) {
    var el = element.createElement;
    var __ = i18n.__;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType( 'dahu-booking/widget', {

        edit: function () {
            var blockProps = useBlockProps( {
                className: 'dahu-booking-block-placeholder',
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
                        __( 'Widget de réservation Dahu Booking', 'dahu-booking' )
                    ),
                    el(
                        'span',
                        { style: { fontSize: '0.9rem' } },
                        __( 'Le calendrier de réservation s\'affichera ici sur le site publié.', 'dahu-booking' )
                    )
                )
            );
        },

        save: function () {
            // Le rendu réel est délégué au render_callback PHP (render.php),
            // donc rien à sauvegarder côté contenu : bloc dynamique.
            return null;
        },

    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.i18n
);
