/**
 * MemberDash helper functions for common validations and other helpful functions.
 *
 * @since 1.0.2
 */

/**
 * Appends a new HTML error-box element to a jQuery element.
 *
 * @since 1.0.2
 *
 * @param {jQuery} jQueryObj - The jQuery object to append or manipulate elements.
 *
 * @returns {jQuery|boolean} - The modified jQuery object with the new element or False if jQuery object is not valid.
 */
function mdAppendErrorBox( jQueryObj) {
	// Destructure properties from "args" with default values
	const selector     = '.memberdash-validation-error.memberdash-validation--error-box',
	      elementClass = 'memberdash-validation-error memberdash-validation--error-box';
	// Check if jQuery object is valid and selector is provided
	if ( ! jQueryObj || jQueryObj.length === 0 ) {
		return false;
	}

	// Check if elements matching the selector exist
	if ( jQueryObj.find( selector ).length === 0 ) {
		// Create new HTML element with provided properties and content
		const newElement = `<div class="${elementClass}"></div>`;
		// Append the new element to the jQuery object
		jQueryObj.append( newElement );
	}

	// Return the jQuery object containing elements matching the selector
	return jQueryObj.find( selector );
}
