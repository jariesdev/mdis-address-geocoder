<template>
  <span class="tweened-number">{{ tweeningValue }}</span>
</template>

<script setup>
import {onMounted, ref, watch} from "vue";

const TWEEN = require('@tweenjs/tween.js')

const props = defineProps({
    // The value that we'll be tweening to.
    value: {
        type: Number,
        required: true
    },

    // How long the tween should take. (In milliseconds.)
    tweenDuration: {
        type: Number,
        default: 500
    }
})

const tweeningValue = ref(0)

// This is our main logic block. It handles tweening from a start value to an end value.
const     tween = (start, end) =>  {
    let frameHandler

    // Handles updating the tween on each frame.
    const animate = function (currentTime) {
        TWEEN.update(currentTime)
        frameHandler = requestAnimationFrame(animate)
    }

    const myTween = new TWEEN.Tween({ tweeningValue: start })
        .to({ tweeningValue: end }, props.tweenDuration)
        // Be careful to not to do too much here! It will slow down the app.
        .onUpdate((object) => {
            tweeningValue.value = object.tweeningValue.toFixed(0)
        })
        .onComplete(() => {
            // Make sure to clean up after ourselves.
            cancelAnimationFrame(frameHandler)
        })
        // This actually starts the tween.
        .start()

    frameHandler = requestAnimationFrame(animate)
}

onMounted(() => {
    tween(0, props.value)
})

watch(() => props.value, (oldVal, newVal) => {
    tween(oldVal, newVal)
})
</script>
