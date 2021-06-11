// GET and POST wrapper to handle BigInt problem
const zCaller = {
    get: function(api, data) {
        let jqXHR = $.get(api, data, null, 'text');
        return new Promise(function (resolve, reject) {
            // Workaround: Force response as text to handle Big Integers
            jqXHR.done(function( json, textStatus, jqXHR ) {
                    // Workaround: Convert Big Integers to strings
                    json = json.replace(/([\[:])?(\d{16,})([,\}\]])/g, "$1\"$2\"$3");
                    json = JSON.parse(json);
                    if (json.error != 0) {
                        reject('Error ' + json.error + ': ' + json.message);
                    }
                    resolve(json.data);
                })
                .fail(reject);
        });
    },
    post: function(api, data, isJson = true) {
        let jqXHR = $.ajax({
                type: 'POST',
                url: api,
                data: JSON.stringify(data),
                contentType: "application/json; charset=UTF-8",
                // Workaround: Force response as text to handle Big Integers
                dataType: 'text'
            });
        return new Promise(function (resolve, reject) {
            // Workaround: Force response as text to handle Big Integers
            jqXHR.done(function( json, textStatus, jqXHR ) {
                    // Workaround: Convert Big Integers to strings
                    json = json.replace(/([\[:])?(\d{16,})([,\}\]])/g, "$1\"$2\"$3");
                    json = JSON.parse(json);
                    if (json.error != 0) {
                        let err = Number(json.error).toString();
                        reject('Error ' + json.error + ': ' + json.message);
                    }
                    resolve(json.data);
                })
                .fail(reject);
        });
    }
};

const zGraphApiBaseUrl = 'graph/v2.0/';
const zOpenApiBaseUrl  = 'openapi/v2.0/';


const zGraphApiBaseUrlForSA = 'graph/v2.0/'; 
const zOpenApiBaseUrlForSA  = 'https://graph.zalo.me/v2.0/';// ///// Social API /////
const frend_id = "785881563324472798";
const message ='Test chơi';
const Zalo = {
    ///// Official Account API /////
    oa: {        
        sendMessage: function(message) {
            return zCaller.post(zOpenApiBaseUrl + 'oa/message',
                    message);
        },
        updateFollowerInfo: function(info) {
            return zCaller.post(zOpenApiBaseUrl + 'oa/updatefollowerinfo',
                    {
                        data: JSON.stringify(info)
                    });
        },
        getProfile: function(userId) {
            return zCaller.get(zOpenApiBaseUrl + 'oa/getprofile',
                    {
                        data: JSON.stringify({user_id: userId})
                    });
        },
        getInfo: function() {
            return zCaller.get(zOpenApiBaseUrl + 'oa/getoa');
        },
        getFollowers: function(offset = 0, count = 5) {
            return Caller.get(zOpenApiBaseUrl + 'oa/getfollowers',
                    {
                        data: JSON.stringify({
                            offset: offset,
                            count: count
                        })
                    });
        },
        getRecentChats: function(offset = 0, count = 5) {
            return zCaller.get(zOpenApiBaseUrl + 'oa/listrecentchat',
                    {
                        data: JSON.stringify({
                            offset: offset,
                            count: count
                        })
                    });
        },
        getConversation: function(userId, offset = 0, count = 5) {
            return zCaller.get(zOpenApiBaseUrl + 'oa/conversation',
                    {
                        // Workaround: Avoid big integer problem (userId)
                        data: '{user_id:'+userId+',offset:'+offset+',count:'+count+'}'
                    });
        },
        getTags: function() {
            return zCaller.get(zOpenApiBaseUrl + 'oa/tag/gettagsofoa');
        },
        assignTag: function(userId, tag) {
            return zCaller.post(zOpenApiBaseUrl + 'oa/tag/tagfollower',
                    {
                        user_id: userId,
                        tag_name: tag
                    });
        },
        unassignTag: function(userId, tag) {
            return zCaller.post(zOpenApiBaseUrl + 'oa/tag/rmfollowerfromtag',
                    {
                        user_id: userId,
                        tag_name: tag
                    });
        },
        removeTag: function(tag) {
            return zCaller.post(zOpenApiBaseUrl + 'oa/tag/rmtag',
                    {
                        tag_name: tag
                    });
        }
    },
    ///// Article API /////
    ///// Shop API /////
    ///// Food API /////
    ///// Social API /////
   
    me:{
        sendMessage: function(message,frend_id) {
            return zCaller.post(zOpenApiBaseUrlForSA + 'me/message',message,frend_id);
        }
    }



};