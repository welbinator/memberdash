/*! MemberDash - v1.0.0
 * Copyright (c) 2019; * Licensed GPLv2+ */
/*!------------------------------------------------------
 * jQuery nearest v1.0.3
 * http://github.com/jjenzz/jQuery.nearest
 * ------------------------------------------------------
 * Copyright (c) 2012 J. Smith (@jjenzz)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */
( function( $, d ) {
	$.fn.nearest = function( selector ) {
		let self, nearest, el, s, p,
			hasQsa = d.querySelectorAll;

		function update( elm ) {
			nearest = nearest ? nearest.add( elm ) : $( elm );
		}

		this.each( function() {
			self = this;

			$.each( selector.split( ',' ), function() {
				s = $.trim( this );

				if ( ! s.indexOf( '#' ) ) {
					// selector starts with an ID
					update( ( hasQsa ? d.querySelectorAll( s ) : $( s ) ) );
				} else {
					// is a class or tag selector
					// so need to traverse
					p = self.parentNode;
					while ( p ) {
						el = hasQsa ? p.querySelectorAll( s ) : $( p ).find( s );
						if ( el.length ) {
							update( el );
							break;
						}
						p = p.parentNode;
					}
				}
			} );
		} );

		return nearest || $();
	};
}( jQuery, document ) );
