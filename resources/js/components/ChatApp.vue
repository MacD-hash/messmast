<template>
    <div class="chat-app" >
        <Conversation :contact="selectedContact" :messages="messages" @new="saveNewMessage"/>
        <ContactsList :contacts="contacts" @selected="startConversationWith"/>
    </div>
</template>

<script>
    import Conversation from './Conversation'
    import ContactsList from './ContactsList'
    export default {
        props: {
            user: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                selectedContact: null,
                messages: [],
                contacts: []
            }
        },
        methods: {
            startConversationWith(contact) {
                this.updateUnreadCount(contact, true)
                axios.get(`/conversation/${contact.id}`)
                    .then((response) => {
                        this.messages = response.data;
                        this.selectedContact = contact;
                    })
            },
            saveNewMessage(message) {
                this.messages.push(message);
            },
            // saveNewMessage(text) {
            //     this.messages.push(text);
            // },
            handleIncoming(message) {
                if(this.selectedContact && message.from == this.selectedContact) {
                    this.saveNewMessage(message);
                    // this.messages.push(message)
                    return;
                }
                // alert(message.text);
                this.updateUnreadCount(message.from_contact, false)
            },
            updateUnreadCount(contact, reset) {
                this.contacts = this.contacts.map((single) => {
                    if(single.id !== contact.id) {
                        return single;
                    }
                    if(reset)
                        single.unread = 0;
                    else 
                        single.unread += 1;
                    
                    return single;
                })
            }
        },
        mounted() {
            // console.log(this.user)
            Echo.private(`messages${this.user.id}`)
                .listen('NewMessage',(e) => {
                    this.handleIncoming(e.message);
                })
            axios.get('/contacts')
                .then((response) => {
                    console.log(response.data);
                    this.contacts = response.data;
                });
        },
        components: {Conversation, ContactsList}
    }
</script>
<style lang="scss" scoped>
    .chat-app {
        display: flex;
    }
</style>