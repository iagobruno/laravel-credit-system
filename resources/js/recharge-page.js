const form = document.getElementById('recharge-form')
const amountInput = document.getElementById('amount-input')
const amountHiddenInput = document.querySelector('input[name="amount"]')

SimpleMaskMoney.args = {
    allowNegative: false,
    fixed: true,
    fractionDigits: 2,
    decimalSeparator: ',',
    thousandsSeparator: '.',
    cursor: 'move'
}

amountInput.addEventListener('input', function () {
    amountInput.value = SimpleMaskMoney.formatToMask(amountInput.value)

    updateHiddenAmountInput()
})
amountInput.addEventListener('blur', function () {
    const valueWithoutDecimals = Math.floor(SimpleMaskMoney.formatToNumber(amountInput.value)) + '00'
    amountInput.value = SimpleMaskMoney.formatToMask(valueWithoutDecimals)

    updateHiddenAmountInput()
})

function sumToAmountInput(amount) {
    const newValue = SimpleMaskMoney.formatToNumber(amountInput.value) + amount
    amountInput.value = SimpleMaskMoney.formatToMask(newValue + '.00')

    updateHiddenAmountInput()
}
window.sumToAmountInput = sumToAmountInput

function updateHiddenAmountInput() {
    amountHiddenInput.value = SimpleMaskMoney.formatToNumber(amountInput.value)
}
updateHiddenAmountInput()


const hasCardInputs = document.getElementsByClassName('card-inputs').length > 0
const paymentErrorOutput = document.getElementById('card-errors')
const cardHolderName = document.getElementById('card-holder-name-input')
const button = form.querySelector('button[type="submit"]:not(disabled)')

const keys = JSON.parse(document.getElementById('stripe-keys').innerText)
const stripe = Stripe(keys.stripe_pub_key)

const card = stripe.elements().create('card', { hidePostalCode: true })
card.mount('#card-element')

card.addEventListener('change', function (event) {
    if (event.error) {
        showCardError(event.error.message)
    } else {
        showCardError('')
    }
})

form.addEventListener('submit', async function (evt) {
    if (hasCardInputs) {
        evt.preventDefault()
    }
    button.setAttribute('disabled', 'true')

    const { setupIntent, error } = await stripe.confirmCardSetup(
        keys.setup_secret,
        {
            payment_method: {
                card,
                billing_details: {
                    name: cardHolderName.value
                }
            }
        }
    )

    if (error) {
        return showCardError(`Payment failed: ${error.message}`)
    }

    submitForm(setupIntent.payment_method)
})

function submitForm(payment_method) {
    const hiddenInput = document.createElement('input')
    hiddenInput.setAttribute('type', 'hidden')
    hiddenInput.setAttribute('name', 'payment_method')
    hiddenInput.setAttribute('value', payment_method)
    form.appendChild(hiddenInput)

    form.submit()
}

function showCardError(msg) {
    paymentErrorOutput.textContent = msg ? msg : ''
    if (msg) button.setAttribute('disabled', 'true')
    else button.removeAttribute('disabled')
}
