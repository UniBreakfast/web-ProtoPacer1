
const StrGen = (()=>{
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",
        UPPERS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
        lowers = "abcdefghijklmnopqrstuvwxyz",
        UP_CONSONS = "BCDFGHJKLMNPQRSTVWXZ",
        UP_VOWELS = "AEIOUY",
        low_consons = "bcdfghjklmnpqrstvwxz",
        low_vowels = "aeiouy",
        NUMB3RS = "1234567890";

  const rnum = cap => Math.floor(Math.random()*cap),
        rch = ()=> chars.charAt(rnum(62)),
        rV = ()=> UP_VOWELS.charAt(rnum(6)),
        rv = ()=> low_vowels.charAt(rnum(6)),
        rC = ()=> UP_CONSONS.charAt(rnum(20)),
        rc = ()=> low_consons.charAt(rnum(20)),
        rN = ()=> NUMB3RS.charAt(rnum(10)),
        rU = ()=> UPPERS.charAt(rnum(26)),
        rl = ()=> lowers.charAt(rnum(26));
  function add_rv() {
    this.result += low_vowels.charAt(rnum(6));
    this.prev_vowel = this.last_vowel;
    this.last_vowel = true;
  }
  function add_rc() {
    this.result += low_consons.charAt(rnum(20));
    this.prev_vowel = this.last_vowel;
    this.last_vowel = false;
  }

  function password(min_length, max_length){
    min_length = min_length || 16
    var length, result = "";
    if (max_length) length = rnum(max_length-min_length+1)+min_length;
    else length = min_length;
    for (var i = 0; i < length; i++)
      result += rch();
    return result;
  }

  function word_(length, exact){
    length = length || 12;
    const state = {result: "", last_vowel: false, prev_vowel: false},
          arv = add_rv.bind(state), arc = add_rc.bind(state);
    if (!exact) length = rnum(length)+1;

    if (length == 1) arv();
    else {
      if (rnum(4)) arc();
      else arv();

      for (var i=1; i<length; i++){
        if (state.last_vowel){
          if (state.prev_vowel) arc();
          else {
            if (rnum(10)) arc(); //second vowel probability is 1:X
            else arv();
          }
        }
        else {
          if (state.prev_vowel){
            if (rnum(6)) arv(); //second consonant probability is 1:X
            else arc();
          }
          else arv();
        }
      }
    }
    return state.result
  }

  function word(length, exact){
    length = length || 9;
    var result = "", last_vowel, prev_vowel;
    if (!exact) length = rnum(length)+1;

    if (length == 1) result = rv();
    else {
      if (rnum(4)) result += rc();
      else {
        result += rv();
        last_vowel = true;
      }

      for (var i=1; i<length; i++){
        if (last_vowel){
          if (prev_vowel){
            result += rc();
            last_vowel = false;
          }
          else {
            if (rnum(10)){ //second vowel probability is 1:X
              result += rc();
              last_vowel = false;
            }
            else result += rv();
          }
          prev_vowel = true;
        }
        else {
          if (prev_vowel){
            if (rnum(6)){ //second consonant probability is 1:X
              result += rv();
              last_vowel = true;
            }
            else result += rc();
          }
          else {
            result += rv();
            last_vowel = true;
          }
          prev_vowel = false;
        }
      }
    }
    return result
  }

  function Word(length, exact){
    length = length || 10;
    var result = word(length, exact);
    return result.charAt(0).toUpperCase() + result.substr(1)
  }

  function phrase(length, word_max_len, exact){
    length = length || 25;
    word_max_len = word_max_len || 9;
    var result = '';
    if (!exact) length = rnum(length)+1;
    while (result.length < length) {
      if (length-result.length == 1) result += word(1);
      else result += word(word_max_len) + ' ';
    }
    result = result.slice(0, length);
    if (result[result.length-1] == ' ') result = result.slice(0, result.length-1);
    return result
  }

  function Phrase(length, word_max_len, exact){
    length = length || 25;
    word_max_len = word_max_len || 9;
    var result = phrase(length, word_max_len, exact);
    return result.charAt(0).toUpperCase() + result.substr(1)
  }

  function Sentence(length, word_max_len, exact, terminator){
    length = length || 100;
    word_max_len = word_max_len || 9;
    var result = '';
    if (!exact) length = rnum(length)+1;
    while (result.length < length) {
      if (length-result.length == 1) result += word(1);
      else {
        result += word(word_max_len);
        if (!rnum(7)) result += ', ';
        else if (!rnum(40)) result += ' - ';
        else if (!rnum(60)) result += ': ';
        else result += ' ';
      }
    }
    if (result[result.length-1] == ' ') result = result.slice(0, result.length-1);
    if (result[result.length-1] == ',') result = result.slice(0, result.length-1);
    if (result[result.length-1] == ':') result = result.slice(0, result.length-1);
    if (result.slice(result.length-2, result.length) == ' -')
      result = result.slice(0, result.length-2);
    result = result.slice(0, length);

    if (terminator) return result.charAt(0).toUpperCase() + result.substr(1) + terminator;
    else {
      if (!rnum(12)) return result.charAt(0).toUpperCase() + result.substr(1) + '!';
      else if (!rnum(50)) return result.charAt(0).toUpperCase() + result.substr(1) + '?';
      else if (!rnum(40)) return result.charAt(0).toUpperCase() + result.substr(1) + '...';
      else return result.charAt(0).toUpperCase() + result.substr(1) + '.';
    }
  }

  function Text(length, exact){
    length = length || 750;
    var result = '';
    if (!exact) length = rnum(length)+1;
    while (result.length < length-3) {
      if (length-result.length <= 100) result += Sentence(length-result.length, 9, 1);
      else result += Sentence()+' ';
    }
    return result
  }

  function number(length, exact){
    length = length || 4;
    var result = '';
    if (!exact) length = rnum(length)+1;
    result = NUMB3RS.charAt(rnum(9));
    for (var i=1; i<length; i++){
      result += rN();
    }
    return result
  }

  function number0(length, exact){
    length = length || 7;
    var result = '', length0;
    if (!exact) length = rnum(length)+1;
    result = NUMB3RS.charAt(rnum(9));
    length0 = rnum(length);
    for (var i=1; i<length-length0; i++){
      result += rN();
    }
    for (var i=0; i<length0; i++){
      result += '0';
    }
    return result
  }

  return {rnum, password, Word, word, word_, Phrase, phrase, Sentence, Text, number, number0};
})();
