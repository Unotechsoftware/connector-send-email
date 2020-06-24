<template>
    <div class="email-options">

        <div v-if="highlightedNode.type === 'processmaker-communication-send-email'" class="form-group">
          <label>{{ $t('Name') }}</label>
          <input v-model="definition.name" name="name" placeholder type="text" class="form-control"/>
          <small class="form-text text-muted">{{ $t('Enter the name of this element') }}</small>
        </div>

        <div class="form-group">
          <label>{{ $t('Subject') }}</label>
          <input v-model="config.subject" :placeholder="$t('RE:')" class="form-control">
          <small class="form-text text-muted">{{ $t('Email subject') }}</small>
        </div>

        <div class="form-group">
          <label>{{ $t('Body') }}</label>
          <select class="form-control" v-model="config.type">
            <option value="text">{{ $t('Plain Text') }}</option>
            <option value="screen">{{ $t('Display Screen') }}</option>
          </select>

          <textarea class="form-control mt-3" v-show="config.type === 'text'" v-model="config.textBody" rows="5">
          </textarea>

          <modeler-screen-select
            class="p-0"
            v-show="config.type === 'screen'"
            :helper="$t('What Screen Should Be Used For Sending This Email')"
            :params='{type:"EMAIL"}'
            v-model='config.screenRef'>
          </modeler-screen-select>
        </div>

          <user-group-select
            label="Recipients"
            v-bind:multiple="true"
            v-model="usersGroupsSelected"
            ref="userGoupSelect"
          >
          </user-group-select>
          <small class="form-text text-muted">{{ $t('(Select all that apply)') }}</small>

           <add-input
            :label="$t('Add additional emails')"
            :placeholder="$t('enter email')"
            v-model="config.addEmails"
          ></add-input>

          <div class="form-group">
            <!-- <label>{{ $t('Send mail to Requester') }}</label> -->
            <form-checkbox
                class="form-control border-0 inspector-font-size"
                label="Send mail to requester"
                :checked="config.sendToRequester"
                v-model="config.sendToRequester">
            </form-checkbox>
            <small class="form-text text-muted pt-2">{{ $t('Mail will be sent to the requester') }}</small>
          </div>

    </div>

</template>

<script>
  import AddInput from "./AddInput";
  import UserGroupSelect from "./UserGroupSelect";
  import helper from '../../../helper';

  export default {
    components: {
      AddInput,
      UserGroupSelect
    },
    props: ["value"],
    mixins: [helper],
    data() {
      return {
        showConfiguration: false,
        config: {
          subject: '',
          type: 'screen',
          textBody: '',
          screenRef: '',
          addEmails: [],
          users : [],
          groups : [],
          sendToRequester: false
        },
      };
    },
    watch: {
      config: {
        deep: true,
        handler() {
          this.validate();
          this.$emit('input', this.config);
        }
      },
      value: {
        immediate: true,
        handler() {
          if (this.value === null) { return; }
          Vue.set(this, 'config', this.value);
        }
      },
      nodeId() {
        this.validate();
      },
    },
    computed: {
      usersGroupsSelected: {
        get () {
          return { users: this.config.users, groups: this.config.groups};
        },
        set (val) {
          this.config.users = val.users;
          this.config.groups = val.groups;
        }
      },
      warnings: {
        get() {
          return this.$root.$children[0].warnings;
        },
        set(val) {
          this.$root.$children[0].warnings = val;
        }
      }
    },
    methods: {
      validate() {
        // Remove any existing warnings
        this.warnings = this.warnings.filter(w => w._node_id !== this.nodeId);

        if (!this.config.subject) {
          this.addWarning(this.$t('Email Subject Is Missing'));
        }

        if (this.config.type === 'screen') {
          if (!this.config.screenRef) {
            this.addWarning(this.$t('No Screen Selected'));
          }
        } else {
          if (!this.config.textBody) {
            this.addWarning(this.$t('Email Body Text Is Missing'));
          }
        }

        if ( this.config.addEmails && 
          (
            !this.config.addEmails.length ||
            this.config.addEmails.every(r => r === '')
          ) &&
          !this.config.users.length &&
          !this.config.groups.length
        ) {
          this.addWarning(this.$t('Email Has No Recipients'));
        }
      },
      addWarning(text) {
        const warning = {
          _node_id: this.nodeId,
          title: this.$t('Send Email') + ' ' + this.nodeId,
          text
        };
        this.warnings.push(warning);
      }
    },
  };
</script>

<style lang="scss" scoped>
.email-options {
    div.card {
        padding: 0!important;
    }
}
</style>
