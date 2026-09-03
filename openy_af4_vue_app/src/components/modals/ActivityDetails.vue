<template>
  <Modal
    id="activity-finder-activity-details"
    v-model="visible"
    :title="t('Activity details')"
    narrow
    responsive
  >
    <template v-slot:default>
      <div v-if="item" class="activity-details-modal-content">
        <Loading v-if="isLoadingData" />
        <div v-else class="row">
          <div class="col-12 col-xs-12 col-md-6 left-wrapper">
            <div class="left">
              <div class="title">{{ item.name }}</div>
              <div class="description">{{ item.description }}</div>
              <div v-if="item.ages" class="row ages">
                <div class="col-3 col-xs-3">
                  {{ t('Ages:') }}
                </div>
                <div class="col-9 col-xs-9">{{ item.ages }}</div>
              </div>
              <div v-if="item.gender" class="row gender">
                <div class="col-3 col-xs-3">
                  {{ t('Gender:') }}
                </div>
                <div class="col-9 col-xs-9">{{ item.gender }}</div>
              </div>
              <a :href="item.link" target="_blank" class="learn-more">
                {{ t('Learn more about this program') }}
              </a>
            </div>
          </div>
          <div class="col-12 col-xs-12 col-md-6 right-wrapper">
            <div class="right">
              <div class="info-section">
                <div v-if="item.dates" class="item-detail dates">
                  <Icon icon="material-symbols:calendar-today-outline" />
                  <span>
                    <span class="info">{{ item.dates }}</span>
                  </span>
                </div>

                <div class="item-detail schedule">
                  <Icon icon="material-symbols:schedule-outline" />
                  <span class="schedule-items">
                    <span
                      v-for="(schedule, index) in item.schedule"
                      :key="index"
                      class="schedule-item"
                    >
                      <span class="info">{{ schedule.time }}</span>
                      <br />
                      <span class="details">{{ schedule.days }}</span>
                    </span>
                  </span>
                </div>

                <div v-if="item.location" class="item-detail">
                  <Icon icon="material-symbols:location-on-outline" />
                  <span>
                    <span class="info">{{ item.location }}</span>
                    <br />
                    <span v-if="item.roomName" class="details">{{ item.roomName }}</span>
                  </span>
                </div>

                <div v-if="item.instructor" class="item-detail instructor">
                  <Icon icon="material-symbols:person-outline" />
                  <span>
                    <span class="info">{{ item.instructor }}</span>
                    <br />
                    <span v-if="item.substitute" class="details">{{ item.substitute }}</span>
                  </span>
                </div>

                <div v-if="item.price" class="item-detail price">
                  <Icon icon="material-symbols:payments-outline" />
                  <span>
                    <span class="info">{{ item.price }}</span>
                  </span>
                </div>
                <AvailableSpots
                  v-if="!disableSpotsAvailable && item.spots_available !== ''"
                  :spots="Number(item.spots_available)"
                  :wait-list="Number(item.wait_list_availability)"
                  big
                />
              </div>
              <div class="action">
                <template v-if="buttonState === 'default'">
                  <a
                    key="register"
                    role="button"
                    class="btn btn-lg register"
                    :class="{ disabled: isRegisterDisabled }"
                    :href="item.link"
                    target="_blank"
                    @click="register()"
                  >
                    {{ getButtonTitle }}
                  </a>
                  <a
                    v-if="!isBookmarked() && !legacyMode"
                    key="bookmark"
                    role="button"
                    class="bookmark"
                    title="Add bookmark"
                    @click="bookmarkItem()"
                  >
                    <font-awesome-icon icon="bookmark" />
                  </a>
                  <a
                    v-else-if="!legacyMode"
                    key="unbookmark"
                    role="button"
                    class="bookmark bookmarked"
                    title="Remove bookmark"
                    @click="unbookmarkItem()"
                  >
                    <font-awesome-icon icon="bookmark" />
                  </a>
                </template>
                <template v-else-if="buttonState === 'sentToRegister'">
                  <a
                    key="reset"
                    role="button"
                    class="btn btn-lg action-taken"
                    @click="resetAction()"
                  >
                    <span>{{ t('Sent to register') }}</span>
                    <i class="fa fa-redo fa-repeat"></i>
                  </a>
                  <a
                    v-if="!isBookmarked() && !legacyMode"
                    key="bookmark"
                    role="button"
                    class="bookmark"
                    title="Add bookmark"
                    @click="bookmarkItem()"
                  >
                    <font-awesome-icon icon="bookmark" />
                  </a>
                  <a
                    v-else-if="!legacyMode"
                    key="unbookmark"
                    role="button"
                    class="bookmark bookmarked"
                    title="Remove bookmark"
                    @click="unbookmarkItem()"
                  >
                    <font-awesome-icon icon="bookmark" />
                  </a>
                </template>
                <template v-else-if="buttonState === 'itemBookmarked'">
                  <a
                    key="reset"
                    role="button"
                    class="btn btn-lg action-taken"
                    @click="resetAction()"
                  >
                    <span>{{ t('Item bookmarked') }}</span>
                    <i class="fa fa-times-circle fa-times-circle-o"></i>
                  </a>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script>
import client from '@/client/index.js'
import Modal from '@/components/modals/Modal.vue'
import AvailableSpots from '@/components/AvailableSpots'
import Loading from '@/components/Loading.vue'
import { Icon } from '@iconify/vue'

export default {
  name: 'ActivityDetailsModal',
  components: {
    Modal,
    AvailableSpots,
    Loading,
    Icon
  },
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    item: {
      type: Object,
      required: true
    },
    cartItems: {
      type: Array,
      required: true
    },
    legacyMode: {
      type: Boolean,
      required: true
    },
    disableSpotsAvailable: {
      type: Boolean,
      required: true
    },
    requestMoreInfo: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      visible: this.modelValue,
      buttonState: 'default',
      // Flag to show if the data request is in progress.
      isLoadingData: false
    }
  },
  computed: {
    getButtonTitle() {
      let title = this.t('Register')
      // parseInt('') -> NaN
      // parseInt('0') -> 0
      if (parseInt(this.item.spots_available) === 0) {
        title = this.item.wait_list_availability > 0 ? this.t('Waiting list') : this.t('Full')
      }
      return title
    },
    isRegisterDisabled() {
      // parseInt('') -> NaN
      // parseInt('0') -> 0
      return parseInt(this.item.spots_available) === 0 && !this.item.wait_list_availability
    }
  },
  watch: {
    modelValue() {
      this.visible = this.modelValue
    },
    visible() {
      this.$emit('update:modelValue', this.visible)
      if (this.visible) {
        this.loadData()
        this.buttonState = 'default'
      }
    }
  },
  methods: {
    isBookmarked() {
      let bookmarked = false
      this.cartItems.forEach(item => {
        if (
          item.item.product_id === this.item.product_id &&
          item.item.nid === this.item.nid
        ) {
          bookmarked = true
        }
      })

      return bookmarked
    },
    register() {
      this.buttonState = 'sentToRegister'
      this.trackEvent('register', 'Click in activity details', this.item.product_id)
    },
    bookmarkItem() {
      this.buttonState = 'default'
      this.trackEvent('bookmark', 'Click in activity details', this.item.product_id)
      this.$emit('bookmark')
    },
    unbookmarkItem() {
      this.trackEvent('unbookmark', 'Click in activity details', this.item.product_id)
      this.$emit('unbookmark')
    },
    resetAction() {
      this.buttonState = 'default'
    },
    loadData() {
      if (!this.requestMoreInfo) {
        return
      }
      if (this.item.moreInfoLoaded) {
        return
      }

      this.isLoadingData = true
      client('more_info')
        .request({
          params: {
            log: this.item.log_id,
            details: this.item.name,
            nid: this.item.nid,
            program: this.item.program_id,
            offering: this.item.offering_id,
            location: this.item.location_id
          }
        })
        .then(response => {
          this.isLoadingData = false
          this.item.description = response.data.description
          this.item.program_name = response.data.program_name
          this.item.spots_available = response.data.spots_available
          this.item.moreInfoLoaded = true
        })
    }
  }
}
</script>

<style lang="scss">
.activity-details-modal-content {
  color: $af-black;

  .row {
    margin-left: -10px !important;
    margin-right: -10px !important;

    @include media-breakpoint-up('lg') {
      display: flex;
    }
  }

  [class*='col-'] {
    padding-left: 10px;
    padding-right: 10px;
  }

  .right-wrapper {
    display: flex;
    background-color: $af-light-gray;
  }

  .left {
    margin: 20px 25px;

    @include media-breakpoint-up('lg') {
      margin-right: 0;
    }

    .title,
    .description,
    .ages,
    .gender {
      margin-bottom: 10px;
    }

    .title {
      font-size: 18px;
      line-height: 28px;
      font-weight: 700;
    }

    .description,
    .ages,
    .gender,
    .learn-more {
      font-size: 14px;
      line-height: 20px;
    }

    .learn-more {
      color: $af-blue;
      text-decoration: underline;
    }
  }

  .right {
    margin: 20px 10px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 100%;

    @media (min-width: 1060px) {
      margin-left: 0;
    }

    .info-section {
      margin-bottom: 20px;
    }

    .item-detail {
      display: flex;
      margin-bottom: 10px;

      &:last-child {
        margin-bottom: 0;
      }

      svg {
        color: $af-black;
        margin-right: 10px;
        position: relative;
        top: 6px;
        height: 1.2rem;
        width: 1.2rem;
      }

      &.location {
        svg {
          top: 3px;
          color: $af-black;
          height: 1.2rem;
          width: 1.2rem;
        }
      }

      .schedule-items {
        display: flex;

        @include media-breakpoint-up('md') {
          flex-direction: column;
        }

        .schedule-item {
          margin-right: 10px;

          @include media-breakpoint-up('md') {
            margin-right: 0;
          }

          &:last-child {
            margin-right: 0;
          }
        }
      }

      .info {
        font-size: 14px;
        line-height: 20px;
      }

      .details {
        font-size: 14px;
        line-height: 20px;
      }

      .fa,
      .svg-inline--fa {
        font-size: 20px;
        color: $af-dark-gray;
        margin-right: 10px;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
      }
    }

    .action {
      padding-right: 10px;
      border-radius: 5px;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;

      &:last-child {
        margin-bottom: 0;
      }

      .register {
        background-color: $af-violet;
        color: $white;
        flex-grow: 1;
        border-radius: $af-border-radius;
        font-size: 18px;
        line-height: 50px;
        font-weight: bolder;
        padding: 0;
      }

      .bookmark {
        display: inline-block;
        line-height: 46px;
        width: 50px;
        height: 50px;
        border-radius: 5px;
        text-align: center;
        margin-left: 10px;
        border: 2px solid $af-blue;

        .fa,
        .svg-inline--fa {
          color: $af-blue;
          font-size: 18px;
          line-height: 46px;
        }
      }

      .bookmarked {
        border: none;
        background-color: $af-green;

        .fa,
        .svg-inline--fa {
          color: $white;
          line-height: 50px;
        }
      }

      .action-taken {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: $af-light-gray;
        color: $af-darker-gray;
        flex-grow: 1;
        border-radius: 5px;
        font-size: 18px;
        line-height: 32px;
        font-weight: bolder;
        padding: 0 15px 0 20px;
        white-space: normal;

        .fa,
        .svg-inline--fa {
          font-size: 20px;
        }
      }
    }
  }

  .age-icons {
    line-height: 50px;
    width: 50px;
    text-align: center;
    margin-right: 10px;
  }
  .available-spots-component {
    display: inline-block;
    margin-top: 16px;
  }
}
</style>
