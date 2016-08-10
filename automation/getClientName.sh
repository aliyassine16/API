curl -i \
    -H "Accept: application/json" \
    -X POST -d '{"ClientId":"admin","ApiKey":"5f4dcc3b5aa765d61d8327deb882cf99","AccessToken":"1234-5678"}' \
    http://api.local/api/client/getClientName/
echo -e "\r\n\r\n\r\n\r\n\r\n"
    curl -i \
        -H "Accept: application/json" \
        -X POST -d '{"ClientId":"admin","ApiKey":"5f4dcc3b5aa765d61d8327deb882cf99","AccessToken":"1234-5678-1010-5050"}' \
        http://api.local/api/client/getClientName/