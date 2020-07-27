<template>
    <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="m-0">{{ $t('Notifications') }}</h6>
                <b-button @click="addNotification()"
                          variant="link"
                          class="p-0 text-secondary"
                >
                    <i class="fas fa-plus-square"></i>
                </b-button>
            </div>
            <b-collapse id="email-configuration" v-model="showConfig">
                <b-card :header="configHeader" v-if="currentNotification">
                    <email-options v-model="currentNotification"></email-options>

                    <div class="form-group">
                        <!-- <label>{{ $t('Send mail to assignee') }}</label> -->
                        <form-checkbox
                            class="form-control border-0 inspector-font-size"
                            label="Send mail to assignee"
                            :checked="currentNotification.sendToAssignee"
                            v-model="currentNotification.sendToAssignee">
                        </form-checkbox>
                        <small class="form-text text-muted pt-2">{{ $t('Mail will be sent to the assignee') }}</small>
                    </div>

                    <div class="form-group">
                        <form-checkbox
                            class="form-control border-0 inspector-font-size"
                            label="Send mail to participants"
                            :checked="currentNotification.sendToParticipants"
                            v-model="currentNotification.sendToParticipants">
                        </form-checkbox>
                        <small class="form-text text-muted pt-2">{{ $t('Mail will be sent to the participants') }}</small>
                    </div>

                    <div class="form-group">
                        <form-checkbox
                            class="form-control border-0 inspector-font-size"
                            label="Attach Uploaded File"
                            :checked="currentNotification.attachUploadFile"
                            v-model="currentNotification.attachUploadFile">
                        </form-checkbox>
                        <small class="form-text text-muted pt-2">{{ $t('Uploaded File will be sent as an attachment in email.') }}</small>
                    </div>

                    <div class="form-group" v-if="currentNotification.attachUploadFile">
                        <label>{{ $t('No. Of Email Attachments.') }}</label>
                        <input class="form-control" v-model="currentNotification.noOfAttachments" type="number" min="1" v-on:keydown="noOfAttachmentsInValidate">
                        <small class="form-text text-muted">{{ $t('Specify the maximum number of attachments to be sent in email.') }}</small>
                    </div>

                    <div class="form-group">
                        <label>{{ $t('Send At') }}</label>
                        <select class="form-control" v-model="currentNotification.sendAt">
                            <option value="task-start">{{ $t('Task Start') }}</option>
                            <option value="task-end">{{ $t('Task Completion') }}</option>
                        </select>
                        <small class="form-text text-muted">{{ $t('Choose when this email will be sent to recipients') }}</small>
                    </div>

                    <div class="form-group">
                        <label>{{ $t('Expression') }}</label>
                        <input :placeholder="$t('varname == true')" class="form-control" v-model="currentNotification.expression">
                        <small class="form-text text-muted">{{ $t('This notification will only be sent if the expression is true.') }}</small>
                    </div>

                    <b-card-footer class="text-right">
                        <b-button size="sm" variant="outline-secondary" @click="onCancel" >{{ $t('Cancel') }}</b-button>
                        <b-button type="submit" size="sm" variant="secondary" @click="closeForm">{{ $t('Close') }}</b-button>
                    </b-card-footer>
                </b-card>
                <div v-else>
                    No current notification
                </div>
            </b-collapse>

            <b-card no-body v-show="showDeleteNotification"
                    bg-variant="danger"
                    text-variant="white"
            >
                <div class="p-3">{{ $t('Are you sure you want to delete this notification?') }}</div>
                <b-card-footer class="text-right">
                    <b-button size="sm" variant="light" @click="showDeleteNotification = !showDeleteNotification">{{ $t('Cancel')  }}</b-button>
                    <b-button size="sm" variant="link text-white" @click="onDelete()">{{ $t('Delete') }}</b-button>
                </b-card-footer>
            </b-card>

             <table class="table table-sm table-striped">
                 <tbody>
                     <tr v-for="(notification, index) in config.email_notifications.notifications" :key="index">
                         <td>
                             <i class="far fa-envelope"></i> {{ notification.subject}}
                         </td>
                         <td class="text-right actions">
                             <b-button variant="link" class="p-0 text-secondary" :title="$t('edit')" @click="onEdit(notification, index)"><i class="fas fa-cog"></i></b-button>
                             <b-button variant="link" class="p-0 text-secondary" :title="$t('duplicate')" @click="onDuplicate(notification)"><i class="far fa-copy"></i></b-button>
                             <b-button variant="link" class="p-0 text-secondary" :title="$t('delete')" @click="onConfirmDelete(notification, index)" ><i class="fas fa-trash-alt"></i></b-button> </td>
                     </tr>
                 </tbody>
             </table>
    </div>
</template>

<script>
import EmailOptions from "./EmailOptions";
import helper from '../../../helper';

export default {
    components: {
        EmailOptions
    },
    mixins: [helper],
    props: {
        value: {
        type: Array,
        default: () => []
      }
    },
    data() {
        return {
            showDeleteNotification: false,
            confirmDelete: false,
            config: {
                email_notifications: {
                    notifications: [],
                },
            },
            initNotification: {
                sendAt: 'task-start',
                expression: '',
                subject: '',
                type: 'text',
                textBody: '',
                addEmails: [],
                users: [],
                groups: [],
                screenRef: null,
                sendToAssignee: false,
                sendToParticipants: false,
                attachUploadFile: false,
                noOfAttachments: 4
            },
            currentNotification: null,
            currentNotificationIndex: null,
            originalNotification: null,
            deleteIndex: null,
            showConfig: false,
            configHeader: '',

        }
    },
    watch: {
        config: {
            deep: true,
            handler() {
                this.setNodeConfig();
            }
        },
    },
    methods: {
        node() {
            return this.highlightedNode.definition;
        },
        addNotification() {
            this.showConfig = false;
            let newNotification = Object.assign({}, this.initNotification);
            newNotification.subject = this.$t('RE') + ': ' + this.node().name;
            newNotification.textBody = this.$t('You have a pending task') + ': ' + this.node().name;
            this.config.email_notifications.notifications.push(newNotification);
            this.currentNotificationIndex = this.config.email_notifications.notifications.length - 1;
            this.currentNotification = newNotification;
            this.configHeader = this.$t('Add Notification');
            this.showConfig = true;
        },
        getNodeConfig() {
            if (_.get(this.node(), 'config')) {
                this.config = JSON.parse(_.get(this.node(), 'config'));
                if (!('email_notifications' in this.config)) {
                    this.config.email_notifications = { notifications: [] };
                }
            }
        },
        setNodeConfig() {
            Vue.set(this.node(), 'config', JSON.stringify(this.config));
        },
        onEdit(notification, index) {
            if (this.showConfig) {
                // Just close the open one
                this.showConfig = false;
                return;
            }
            this.originalNotification = _.cloneDeep(notification);
            this.currentNotification = notification;
            this.currentNotificationIndex = index;
            this.configHeader = this.$t('Edit Notification');
            this.showConfig = true
        },
        onDuplicate(notification) {
            let duplicateNoticiation = _.cloneDeep(notification);
            duplicateNoticiation.subject = duplicateNoticiation.subject + this.$t(' copy');
            this.config.email_notifications.notifications.push(Object.assign({}, duplicateNoticiation));
        },
        onConfirmDelete(notification, index) {
            this.showDeleteNotification = true;
            this.deleteIndex = index;
        },
        onDelete() {
            this.$delete(this.config.email_notifications.notifications, this.deleteIndex);
            this.showDeleteNotification = false;
            this.deleteIndex = null;
        },
        onCancel() {
            if (this.originalNotification) {
                this.$set(
                    this.config.email_notifications.notifications,
                    this.currentNotificationIndex,
                    _.cloneDeep(this.originalNotification)
                );
            } else {
                this.$delete(this.config.email_notifications.notifications, this.currentNotificationIndex);
            }
            this.currentNotification = null;
            this.currentNotificationIndex - null;
            this.originalNotification = null;
            this.showConfig = false;
        },
        closeForm() {
            this.showConfig = false
        },
        noOfAttachmentsInValidate(event) {
            if (event.key === "-" || event.key === "e") {
            event.preventDefault();
            return;
            }
        },
    },
    mounted() {
        this.getNodeConfig();
    }
}
</script>

<style scoped>
.table td.actions {
    white-space: nowrap;
}
div.card-body {
    padding: 0.5em;
}
</style>
