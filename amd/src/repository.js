import {call as fetchMany} from 'core/ajax';

export const getSecuredNode = args => fetchMany([{
    methodname: 'mod_edusharing_get_secured_node',
    args: args
}])[0];

export const getCurrentUser = () => fetchMany([{
    methodname: 'mod_edusharing_get_current_user',
    args: {}
}])[0];

export const sendXapiStatement = args => fetchMany([{
    methodname: 'core_xapi_statement_post',
    args: args
}])[0];
