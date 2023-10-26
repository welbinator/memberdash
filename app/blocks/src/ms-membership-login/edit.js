import { InspectorControls } from '@wordpress/block-editor';
import {TextControl, PanelBody, PanelRow, ToggleControl} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
export default function Edit( props ) {
    function title( value ) {
        props.setAttributes( { title: value } );
    }
    function show_labels( value ) {
        props.setAttributes( { show_labels: value } );
    }
    function redirect_login( value ) {
        props.setAttributes( { redirect_login: value } );
    }
    function redirect_logout( value ) {
        props.setAttributes( { redirect_logout: value } );
    }
    function header( value ) {
        props.setAttributes( { header: value } );
    }
    function register( value ) {
        props.setAttributes( { register: value } );
    }
    function autofocus( value ) {
        props.setAttributes( { autofocus: value } );
    }
    function holder( value ) {
        props.setAttributes( { holder: value } );
    }
    function holderclass( value ) {
        props.setAttributes( { holderclass: value } );
    }
    function item( value ) {
        props.setAttributes( { item: value } );
    }
    function itemclass( value ) {
        props.setAttributes( { itemclass: value } );
    }
    function prefix( value ) {
        props.setAttributes( { prefix: value } );
    }
    function postfix( value ) {
        props.setAttributes( { postfix: value } );
    }
    function wrapwith( value ) {
        props.setAttributes( { wrapwith: value } );
    }
    function wrapwithclass( value ) {
        props.setAttributes( { wrapwithclass: value } );
    }
    function form( value ) {
        props.setAttributes( { form: value } );
    }
    function nav_pos( value ) {
        props.setAttributes( { nav_pos: value } );
    }
    function show_note( value ) {
        props.setAttributes( { show_note: value } );
    }
    function label_username( value ) {
        props.setAttributes( { label_username: value } );
    }
    function label_password( value ) {
        props.setAttributes( { label_password: value } );
    }
    function label_remember( value ) {
        props.setAttributes( { label_remember: value } );
    }
    function label_log_in( value ) {
        props.setAttributes( { label_log_in: value } );
    }
    function id_login_form( value ) {
        props.setAttributes( { id_login_form: value } );
    }
    function id_username( value ) {
        props.setAttributes( { id_username: value } );
    }
    function id_password( value ) {
        props.setAttributes( { id_password: value } );
    }
    function id_remember( value ) {
        props.setAttributes( { id_remember: value } );
    }
    function id_login( value ) {
        props.setAttributes( { id_login: value } );
    }
    function show_remember( value ) {
        props.setAttributes( { show_remember: value } );
    }
    function value_remember( value ) {
        props.setAttributes( { value_remember: value } );
    }
    function value_username( value ) {
        props.setAttributes( { value_username: value } );
    }
    function label_lost_username( value ) {
        props.setAttributes( { label_lost_username: value } );
    }
    function label_lostpass( value ) {
        props.setAttributes( { label_lostpass: value } );
    }
    function id_lost_form( value ) {
        props.setAttributes( { id_lost_form: value } );
    }
    function id_lost_username( value ) {
        props.setAttributes( { id_lost_username: value } );
    }
    function id_lostpass( value ) {
        props.setAttributes( { id_lostpass: value } );
    }
    return (
        <div className="ms-membership-login">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl help="(login|lost|logout) Defines which form should be displayed. An empty value allows the plugin to automatically choose between login/logout" label="Form" value={ props.attributes.form } onChange={ form } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="The title above the login form" label="Title" value={ props.attributes.title } onChange={ title } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Show labels" checked={ props.attributes.show_labels } onChange={ show_labels } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="The page to display after the user was logged-in" label="Redirect login" value={ props.attributes.redirect_login } onChange={ redirect_login } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="The page to display after the user was logged out" label="Redirect logout" value={ props.attributes.redirect_logout } onChange={ redirect_logout } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Header" checked={ props.attributes.header } onChange={ header } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Register" checked={ props.attributes.register } onChange={ register } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Autofocus" checked={ props.attributes.autofocus } onChange={ autofocus } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Holder" value={ props.attributes.holder } onChange={ holder } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Holderclass" value={ props.attributes.holderclass } onChange={ holderclass } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Item" value={ props.attributes.item } onChange={ item } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Itemclass" value={ props.attributes.itemclass } onChange={ itemclass } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Prefix" value={ props.attributes.prefix } onChange={ prefix } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Postfix" value={ props.attributes.postfix } onChange={ postfix } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Wrapwith" value={ props.attributes.wrapwith } onChange={ wrapwith } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Wrapwithclass" value={ props.attributes.wrapwithclass } onChange={ wrapwithclass } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Nav pos" value={ props.attributes.nav_pos } onChange={ nav_pos } />
                    </PanelRow>
                </PanelBody>

                <PanelBody title="Form Settings">
                    <PanelRow>
                        <ToggleControl label="Show note" checked={ props.attributes.show_note } onChange={ show_note } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label username" value={ props.attributes.label_username } onChange={ label_username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label password" value={ props.attributes.label_password } onChange={ label_password } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label remember" value={ props.attributes.label_remember } onChange={ label_remember } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label log in" value={ props.attributes.label_log_in } onChange={ label_log_in } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id login form" value={ props.attributes.id_login_form } onChange={ id_login_form } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id username" value={ props.attributes.id_username } onChange={ id_username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id password" value={ props.attributes.id_password } onChange={ id_password } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id remember" value={ props.attributes.id_remember } onChange={ id_remember } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id login" value={ props.attributes.id_login } onChange={ id_login } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Show remember" checked={ props.attributes.show_remember } onChange={ show_remember } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Value remember" checked={ props.attributes.value_remember } onChange={ value_remember } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Value username" value={ props.attributes.value_username } onChange={ value_username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label lost username" value={ props.attributes.label_lost_username } onChange={ label_lost_username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label lostpass" value={ props.attributes.label_lostpass } onChange={ label_lostpass } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id lost form" value={ props.attributes.id_lost_form } onChange={ id_lost_form } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id lost username" value={ props.attributes.id_lost_username } onChange={ id_lost_username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Id lostpass" value={ props.attributes.id_lostpass } onChange={ id_lostpass } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-membership-login"
                attributes={ {
                    title: props.attributes.title,
                    show_labels: props.attributes.show_labels,
                    redirect_login: props.attributes.redirect_login,
                    redirect_logout: props.attributes.redirect_logout,
                    header: props.attributes.header,
                    register: props.attributes.register,
                    autofocus: props.attributes.autofocus,
                    holder: props.attributes.holder,
                    holderclass: props.attributes.holderclass,
                    item: props.attributes.item,
                    itemclass: props.attributes.itemclass,
                    prefix: props.attributes.prefix,
                    postfix: props.attributes.postfix,
                    wrapwith: props.attributes.wrapwith,
                    wrapwithclass: props.attributes.wrapwithclass,
                    form: props.attributes.form,
                    nav_pos: props.attributes.nav_pos,
                    show_note: props.attributes.show_note,
                    label_username: props.attributes.label_username,
                    label_password: props.attributes.label_password,
                    label_remember: props.attributes.label_remember,
                    label_log_in: props.attributes.label_log_in,
                    id_login_form: props.attributes.id_login_form,
                    id_username: props.attributes.id_username,
                    id_password: props.attributes.id_password,
                    id_remember: props.attributes.id_remember,
                    id_login: props.attributes.id_login,
                    show_remember: props.attributes.show_remember,
                    value_remember: props.attributes.value_remember,
                    value_username: props.attributes.value_username,
                    label_lost_username: props.attributes.label_lost_username,
                    label_lostpass: props.attributes.label_lostpass,
                    id_lost_form: props.attributes.id_lost_form,
                    id_lost_username: props.attributes.id_lost_username,
                    id_lostpass: props.attributes.id_lostpass,

                } }
            />
        </div>
    );
}
