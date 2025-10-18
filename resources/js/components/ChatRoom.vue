<template>
    <div class="container">
        <a href="?room_id=1" class="btn btn-danger">群聊1</a>
        <a href="?room_id=2" class="btn btn-primary">群聊2</a>
        <hr class="divider">

        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">聊天室</div>
                    <div class="panel-body" ref="panelBody">


                        <div class="messages" ref="messages">
                            <div class="media" v-for="message in messages">
                                <div class="media-left">
                                    <a href="#">
                                        <img class="media-object img-circle" :src="message.avatar">
                                    </a>
                                </div>
                                <div class="media-body">
                                    <p class="time">{{ message.time }}</p>
                                    <h4 class="media-heading">{{ message.name }}</h4>
                                    {{ message.content }}
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">在线用户</div>

                    <div class="panel-body">
                        <ul class="list-group">
                            <li class="list-group-item" v-for="user in users">
                                <img :src="user.avatar" class="img-circle">
                                {{ user.name }}
                            </li>

                        </ul>
                    </div>

                </div>
            </div>
        </div>

        <form @submit.prevent="onSubmit">
            <div class="form-group">
                <label for="user_id">私聊</label>

                <select class="form-control" id="user_id" v-model="user_id">
                    <option>所有人</option>
                    <option :value="user.id" v-for="user in users">{{ user.name }}</option>
                </select>

            </div>

            <div class="form-group">
                <label for="content">内容</label>
                <textarea class="form-control" rows="3" id="content" v-model="content"></textarea>
            </div>

            <button type="submit" class="btn btn-default">提交</button>
        </form>
    </div>
</template>

<script>
import $ from 'jquery';
import axios from 'axios';
var ws = new WebSocket("ws://127.0.0.1:7272");

export default {
    data() {
        return {
            messages: [],
            content: '',
            users: [],
            user_id: ''
        }
    },
    created() {
        // 设置 CSRF token
        // const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        // if (csrfToken) {
        //     axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        // }
        ws.onopen = () => {
            console.log('WebSocket 连接已建立');
            this.connectionStatus = 'connected';
        };

        ws.onmessage = (e) => {
            try {

                // 尝试解析 JSON
                let data;
                try {
                    data = JSON.parse(e.data);
                    console.log(data);
                } catch (jsonError) {
                    // 如果不是 JSON，按文本格式处理 
                    console.log(e.data);
                    return;
                }

                let type = data.type || ''

                switch (type) {
                    case 'init':
                        if (data.client_id) {
                            this.handleInit(data.client_id);
                        } else {
                            console.error('init 消息缺少 client_id');
                        }
                        break;
                    case 'say':
                        this.messages.push(data.data);
                        this.$nextTick(() => {
                            this.scrollToBottom();
                        });
                        break;
                    case 'history':
                        this.messages = data.data;
                        break;
                    case 'users':
                        this.users = data.data;
                        break;
                    case 'logout':
                        // 确保目标属性存在
                        if (this.users && this.users.hasOwnProperty(data.client_id)) {
                            // 使用delete操作符删除属性
                            delete this.users[data.client_id];
                            // 关键步骤：为users赋予一个新对象，触发响应式更新
                            this.users = { ...this.users };
                        }

                        break;
                    case 'ping':
                        ws.send('pong');
                        break; 
                    default:
                        console.log('未知消息类型:', data)
                }
            } catch (error) {
                console.error('解析 WebSocket 消息失败:', error);
                console.error('原始消息:', e.data);
            }

        }
    },

    methods: {


        async handleInit(clientId) {
            try {

                const response = await axios.post('/init', {
                    client_id: clientId
                });
                // console.log('初始化成功:', response.data);

            } catch (error) {
                console.error('初始化失败:', error);

                if (error.response) {
                    // 服务器返回了错误状态码
                    console.error('错误状态:', error.response.status);
                    console.error('错误数据:', error.response.data);
                } else if (error.request) {
                    // 请求发送失败
                    console.error('请求发送失败');
                } else {
                    // 其他错误
                    console.error('错误:', error.message);
                }
            }
        },
        async onSubmit() {

            const resay = await axios.post('/say', {
                content: this.content,
                user_id: this.user_id
            });
            this.content = ''


        },
        scrollToBottom() {
            const panelBody = this.$refs.panelBody;
            const messages = this.$refs.messages;

            if (panelBody && messages) {
                panelBody.scrollTop = messages.scrollHeight;
            }
        },

    },

    beforeUnmount() {
        // 组件销毁时关闭 WebSocket 连接
        if (ws) {
            ws.close();
        }
    }
}
</script>


<style scoped>
.panel-body {
    height: 480px;
    overflow: auto;
}

.media-object.img-circle {
    width: 64px;
    height: 64px;
}

.img-circle {
    width: 48px;
    height: 48px;
}

.time {
    float: right;
}

.media {
    margin-top: 24px;
}
</style>
