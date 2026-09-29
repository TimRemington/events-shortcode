<?php
/**
 * Shortcode: [upcoming_event_cards]
 *
 * Displays a responsive grid of upcoming events pulled from The Events Calendar
 * (tribe_events post type). Shows events that haven't ended yet and start within
 * the next year, excludes internal-only categories, and formats event dates to
 * handle single-day, multi-day, and all-day events cleanly.
 *
 * Attributes:
 *   limit   - Number of events to show. Default 6.
 *   exclude - Comma-separated event category slugs to hide. Default "internal-only".
 *
 * Example: [upcoming_event_cards limit="3" exclude="internal-only,members"]
 *
 * Original work written for a client project; client-identifying details
 * have been anonymized for this sample.
 */

defined( 'ABSPATH' ) || exit;

function uec_upcoming_event_cards_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'limit'   => 6,
            'exclude' => 'internal-only',
        ],
        $atts,
        'upcoming_event_cards'
    );

    $limit   = max( 1, absint( $atts['limit'] ) );
    $exclude = array_filter( array_map( 'sanitize_title', explode( ',', $atts['exclude'] ) ) );

    // Event meta dates are stored in the site's local time.
    $now        = current_datetime();
    $now_str    = $now->format( 'Y-m-d H:i:s' );
    $cutoff_str = $now->modify( '+1 year' )->format( 'Y-m-d H:i:s' ); // Arbitrary future limit

    $query_args = [
        'post_type'      => 'tribe_events',
        'posts_per_page' => $limit,
        'no_found_rows'  => true, // No pagination, so skip the total count query
        'meta_query'     => [
            'relation'     => 'AND',
            // Include events still in progress, not just ones that start in the future
            'end_clause'   => [
                'key'     => '_EventEndDate',
                'value'   => $now_str,
                'compare' => '>=',
                'type'    => 'DATETIME',
            ],
            'start_clause' => [
                'key'     => '_EventStartDate',
                'value'   => $cutoff_str,
                'compare' => '<=',
                'type'    => 'DATETIME',
            ],
        ],
        'orderby'        => [ 'start_clause' => 'ASC' ],
    ];

    if ( $exclude ) {
        $query_args['tax_query'] = [
            [
                'taxonomy' => 'tribe_events_cat',
                'field'    => 'slug',
                'terms'    => $exclude,
                'operator' => 'NOT IN',
            ],
        ];
    }

    $events = new WP_Query( $query_args );

    ob_start();

    if ( $events->have_posts() ) :
        echo '<div class="event-cards-grid">';
        while ( $events->have_posts() ) : $events->the_post();
            $post_id    = get_the_ID();
            $categories = get_the_term_list( $post_id, 'tribe_events_cat', '', ', ' );
            $excerpt    = get_the_excerpt();
            $permalink  = get_permalink();
            $start      = get_post_meta( $post_id, '_EventStartDate', true );
            $end        = get_post_meta( $post_id, '_EventEndDate', true );
            $all_day    = 'yes' === get_post_meta( $post_id, '_EventAllDay', true );

            $date_display = '';

            if ( $start && $end ) {
                $tz       = wp_timezone();
                $start_dt = new DateTimeImmutable( $start, $tz );
                $end_dt   = new DateTimeImmutable( $end, $tz );
                $same_day = $start_dt->format( 'Y-m-d' ) === $end_dt->format( 'Y-m-d' );

                if ( $all_day ) {
                    // All-day events: show dates only, not "12:00 AM – 11:59 PM"
                    $start_fmt    = wp_date( 'F j, Y', $start_dt->getTimestamp() );
                    $date_display = $same_day
                        ? $start_fmt
                        : $start_fmt . ' – ' . wp_date( 'F j, Y', $end_dt->getTimestamp() );
                } else {
                    $start_fmt    = wp_date( 'F j, Y g:i A', $start_dt->getTimestamp() );
                    $end_fmt      = wp_date( $same_day ? 'g:i A' : 'F j, Y g:i A', $end_dt->getTimestamp() );
                    $date_display = "{$start_fmt} – {$end_fmt}";
                }
            }
            ?>
            <div class="event-card">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php echo get_the_post_thumbnail( $post_id, 'large' ); ?>
                <?php endif; ?>

                <div class="event-content">
                    <div>
                        <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                            <div class="event-category"><?php echo wp_kses_post( $categories ); ?></div>
                        <?php endif; ?>

                        <h3 class="event-title">
                            <a href="<?php echo esc_url( $permalink ); ?>" class="event-title-link"><?php the_title(); ?></a>
                        </h3>

                        <?php if ( '' !== $date_display ) : ?>
                            <div class="event-dates">
                                <?php echo esc_html( $date_display ); ?>
                            </div>
                        <?php endif; ?>

                        <p class="event-excerpt"><?php echo wp_kses_post( $excerpt ); ?></p>
                    </div>
                    <a href="<?php echo esc_url( $permalink ); ?>" class="event-button"><?php esc_html_e( 'Learn More', 'upcoming-event-cards' ); ?></a>
                </div>
            </div>
            <?php
        endwhile;
        echo '</div>';
        wp_reset_postdata();
    else :
        echo '<p class="event-cards-empty">' . esc_html__( 'No upcoming events found.', 'upcoming-event-cards' ) . '</p>';
    endif;

    return ob_get_clean();
}
add_shortcode( 'upcoming_event_cards', 'uec_upcoming_event_cards_shortcode' );
