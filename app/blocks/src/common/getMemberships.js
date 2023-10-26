import { dispatch } from '@wordpress/data';

dispatch( 'core' ).addEntities( [ {
	baseURL: '/memberdash-blocks/v1/memberships/list',
	// The 'post' is not a post type - it's the "post" as in /post above. Also, "kind"
	// and "name" are not documented, so let's assume they form the above baseURL..
	kind: 'memberdash-blocks/v1',
	name: 'memberships/list',
	label: 'MemberDash Get Memberships',
} ] );
