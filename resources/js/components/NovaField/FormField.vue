<template>
  <default-field :field="field" :errors="errors">
    <template slot="field">
      <div class="bg-white shadow-lg rounded-lg">
        <div class="flex px-6 py-4" v-for="image in images">
          <div>
            <img style="max-height: 80px" class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0" :src="image.url">
            <div class="flex -mx-3">
              <input
                :ref="image.inputId"
                class="form-file-input select-none"
                type="file"
                :id="image.inputId"
                name="name"
                @change="fileChange"
                :disabled="isReadonly"
              />
              <label
                :for="image.inputId"
                class="text-xs rounded-full mt-1 px-1 py-1 leading-normal border border-primary text-primary hover:bg-primary hover:text-white"
              >
                Replace
              </label>

              <button class="text-xs rounded-full mt-1 ml-1 px-1 py-1 leading-normal border border-danger text-danger hover:bg-danger hover:text-white" v-on:click.prevent="remove">
                Remove
              </button>
            </div>
          </div>
          <div class="w-full">
            <span class="text-sm leading-tight text-grey-dark">
                Image Metadata:
            </span>
            <div class="flex -mx-3 px-3" v-for="(metadata, index) in image.metadata">
              <input
                type="text"
                class="w-1/3 text-xs form-control form-input form-input-bordered"
                v-model="image.metadata[index].key"
              />
              <input
                type="text"
                class="w-full text-xs form-control form-input form-input-bordered"
                v-model="image.metadata[index].value"
              />
              <button class="text-xs rounded-full px-1 py-1 leading-normal bg-white border border-danger text-danger hover:bg-danger hover:text-white" v-on:click.prevent="removeMetadata(image, index)">
                x
              </button>
            </div>
            <div class="mt-1 text-right">
              <button class="text-xs rounded-full px-4 py-1 leading-normal bg-white border border-primary text-primary hover:bg-primary hover:text-white" v-on:click.prevent="addMetadata(image)" >
                Add Metadata Row
              </button>
            </div>
          </div>
        </div>
      </div>

    </template>
  </default-field>
</template>

<script>
  // import ImageLoader from '@laravel-nova/components/ImageLoader'
  import { FormField, HandlesValidationErrors, Errors } from 'laravel-nova'

  export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data: () => ({
      isCollection: false,
      images: [],
    }),

    methods: {
      setInitialValue () {

        this.isCollection = this.field.is_collection
        let images = (this.isCollection) ? this.field.value : [this.field.value]

        this.images = images.map((image, i) => {
          return {
            inputId: 'eloquent-imagery-' + this.field.name + '-' + i,
            url: image.url,
            metadata: Object.keys(image.metadata).map(key => ({'key': key, 'value': image.metadata[key]}))
          }
        })
      },

      fileChange (event) {
        let image = this.images.find(image => {
          return image.inputId === event.target.id
        })

        let file = this.$refs[event.target.id][0].files[0]

        image.url = URL.createObjectURL(file)
        console.log(image.url)

        let reader = new FileReader()

        reader.addEventListener('load', () => {
          image.fileData = reader.result
        })

        reader.readAsDataURL(file)
      },

      remove () {
        alert('hi')
      },

      fill (formData) {
        let serialized = this.images.map(image => {
          let serializedImage = {}

          serializedImage.url = (image.hasOwnProperty('fileData')) ? image.fileData : image.url

          serializedImage.metadata = image.metadata.reduce((object, next) => {
            object[next.key] = next.value
            return object
          }, {})

          return serializedImage
        })

        formData.append(this.field.attribute, this.isCollection ? serialized : serialized.pop())
      },

      addMetadata (image) {
        image.metadata.push({key: '', value: ''})
      },

      removeMetadata (image, index) {
        image.metadata.splice(index, 1)
      }
    }
  }
</script>
