<template>
  <div class="select-path-component">
    <div v-if="image" class="banner" :style="{ background: 'url(' + image + ') center center/cover no-repeat' }">
      <div class="separator"></div>
      <div class="shadow">
        <h1 class="text-center" :class="{ 'visually-hidden': !labelDisplay }">
          {{ label }}
        </h1>
      </div>
    </div>

    <div class="container">
      <div class="row">
        <div class="col-12 col-xs-12">
          <div class="description text-center">
            <p>{{ 'Start your search for an activity!' | t }}</p>
            <p>
              {{ 'Pick any of the categories to find something that works for you.' | t }}
              <a role="button" class=" " @click="viewResults">{{ 'Or, skip to all results.' | t }}</a>
            </p>
          </div>
        </div>
      </div>
      <div class="row paths">
        <div v-for="path in paths" :key="path.id" :class="pathClasses" @click="onClick(path.id)">
          <button type="button" class="path btn">
            <span class="circle">
              <span class="material-symbols-outlined">
                <Icon :icon="path.icon" />
              </span>
            </span>
            <span class="text">{{ path.name }}</span>
          </button>
        </div>
      </div>
      <div class="row">
        <div class="col-12 col-xs-12">
          <div class="search">
            <h4>{{ 'Search by keyword instead' | t }}</h4>
            <label for="form-control">{{ 'Search by keyword' | t }}</label>
            <slot name="search" />
          </div>
        </div>
        <div class="col-12 col-xs-12">
          <div class="homebranch text-center">
            <slot name="home-branch" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Icon } from '@iconify/vue2'

export default {
  name: 'SelectPath',
  components: {
    Icon
  },
  props: {
    value: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    },
    labelDisplay: {
      type: Boolean,
      required: true
    },
    paths: {
      type: Array,
      required: true
    },
    backgroundImage: {
      type: Object,
      required: true
    },
    bsVersion: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      image: this.backgroundImage.mobile
    }
  },
  computed: {
    pathClasses() {
      return this.bsVersion === 4 ? 'col-6 col-md-3' : 'col-xs-6 col-sm-3'
    }
  },
  mounted() {
    this.chooseImage()
    window.addEventListener('resize', this.chooseImage)
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.chooseImage)
  },
  methods: {
    chooseImage() {
      const width = window.document.documentElement.clientWidth
      if (width >= 992) {
        this.image = this.backgroundImage.desktop
      } else {
        this.image = this.backgroundImage.mobile
      }
    },
    onClick(id) {
      this.trackEvent('selectPath', 'Start with ' + id)
      this.$emit('input', id)
      this.$emit('nextStep')
    },
    viewResults() {
      this.$emit('viewResults')
    }
  }
}
</script>

<style lang="scss">
.select-path-component {
  .banner {
    height: 240px;
    color: $white;

    @include media-breakpoint-up('sm') {
      height: 360px;
    }

    @include media-breakpoint-up('lg') {
      height: 400px;
    }

    .separator {
      @include media-breakpoint-up('lg') {
        height: 5px;
        background-color: $af-black;
        opacity: 0.6;
      }
    }

    .shadow {
      background-color: rgba(35, 31, 32, 0.4);
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
    }

    h1 {
      font-size: 48px;
      line-height: 60px;
      margin: 0;
    }
  }

  .row {
    margin-left: -5px;
    margin-right: -5px;

    @include media-breakpoint-up('sm') {
      margin-left: -10px;
      margin-right: -10px;
    }

    @include media-breakpoint-up('lg') {
      margin-left: -20px;
      margin-right: -20px;
    }
  }

  [class*='col-'] {
    padding-left: 5px;
    padding-right: 5px;
    padding-bottom: 5px;

    @include media-breakpoint-up('sm') {
      padding-left: 10px;
      padding-right: 10px;
      padding-bottom: 10px;
    }

    @include media-breakpoint-up('lg') {
      padding-left: 20px;
      padding-right: 20px;
      padding-bottom: 20px;
    }
  }

  .description {
    font-family: var(--ylb-font-family-cachet, Cachet), Verdana, sans-serif;
    margin: 20px 0;

    @include media-breakpoint-up('lg') {
      margin: 40px 0;
    }

    & p:first-child {
      font-family: var(--ylb-font-family-cachet, Cachet), Verdana, sans-serif;
      font-size: 32px;
      line-height: 54px;

      @include media-breakpoint-up('lg') {
        font-size: 48px;
      }
    }

    p {
      font-family: var(--ylb-font-family-verdana, Verdana), sans-serif;
      font-size: 18px;
      line-height: 28px;
    }
  }

  .paths {
    margin-bottom: 5px;

    @include media-breakpoint-up('sm') {
      margin-bottom: 15px;
    }

    @include media-breakpoint-up('lg') {
      margin-bottom: 40px;
    }
  }

  .path {
    text-align: left;
    font-size: 18px;
    line-height: 50px;
    font-weight: bold;
    color: $white;
    margin-bottom: 5px;
    width: 100%;
    padding: 0;
    background-color: $af-light-gray;
    display: flex;
    flex-direction: column;
    align-content: center;
    justify-content: center;
    align-items: center;
    height: 218px;

    &:active,
    &:focus,
    &:hover {
      color: $white;
      background-color: $af-blue;

      .text {
        color: $white;
      }
    }

    @include media-breakpoint-up('md') {
      height: 255px;
    }

    @include media-breakpoint-up('lg') {
      text-align: center;
      font-size: 24px;
      line-height: 36px;
      padding-bottom: 20px;
      margin-bottom: 0;
    }

    .circle {
      background-color: $white;
      border-radius: 50%;
      width: 94px;
      height: 94px;
      display: flex;
      flex-wrap: nowrap;
      align-content: center;
      justify-content: center;
      align-items: center;
      z-index: 0;

      @include media-breakpoint-up('md') {
        width: 130px;
        height: 130px;
      }

      .material-symbols-outlined {
        background-color: #00aeef;
        border-radius: 50%;
        color: var(--ylb-color-white, #fff);
        width: 68px;
        height: 68px;
        display: flex;
        align-items: center;
        justify-content: center;

        @include media-breakpoint-up('md') {
          width: 94px;
          height: 94px;
        }

        svg {
          color: $white;
          height: 48px;
          width: 48px;
        }
      }
    }

    .text {
      font-size: 18px;
      line-height: 28px;
      margin-top: 24px;
      color: $af-black;
    }

    svg {
      width: 50px;
      text-align: center;
      font-size: 20px;
      @include media-breakpoint-up('lg') {
        display: block;
        margin: 30px auto;
        font-size: 40px;
      }
    }

    .material-symbols-outlined {
      width: 50px;
      text-align: center;
      font-size: 20px;

      @include media-breakpoint-up('lg') {
        display: block;
        margin: 0 auto;
        font-size: 40px;
        padding: 30px 0;
      }
    }
  }

  .search {
    h4 {
      font-size: 26px;
      line-height: 34px;
      margin-bottom: 48px;
      text-align: left;

      @include media-breakpoint-up('lg') {
        font-size: 32px;
        text-align: center;
      }
    }

    label {
      font-size: 15px;
      font-weight: 700;
      text-transform: uppercase;
    }
  }

  .search,
  .homebranch {
    max-width: 560px;
    margin: 0 auto;
  }
  .homebranch {
    margin: 24px auto;
    font-size: 20px;
  }
}
</style>
