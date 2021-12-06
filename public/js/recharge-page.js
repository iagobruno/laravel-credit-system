/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!***************************************!*\
  !*** ./resources/js/recharge-page.js ***!
  \***************************************/
var amountInput = document.getElementById('amount-input');
SimpleMaskMoney.args = {
  allowNegative: false,
  fixed: true,
  fractionDigits: 2,
  decimalSeparator: ',',
  thousandsSeparator: '.',
  cursor: 'move'
};
amountInput.addEventListener('input', function () {
  amountInput.value = SimpleMaskMoney.formatToMask(amountInput.value);
});
amountInput.addEventListener('blur', function () {
  var valueWithoutDecimals = Math.floor(SimpleMaskMoney.formatToNumber(amountInput.value)) + '00';
  amountInput.value = SimpleMaskMoney.formatToMask(valueWithoutDecimals);
});

function addAmountToInput(amount) {
  var newValue = SimpleMaskMoney.formatToNumber(amountInput.value) + amount;
  amountInput.value = SimpleMaskMoney.formatToMask(newValue + '.00');
}

window.addAmountToInput = addAmountToInput;
/******/ })()
;