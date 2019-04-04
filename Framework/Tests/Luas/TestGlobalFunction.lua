-- lua Framework/Tests/Luas/TestClass.lua
require "../../Framework/Luas/GlobalFunction"
assert(empty(0) == true)
assert(empty(1) == false)
assert(empty(1.0) == false)
assert(empty(0.0) == true)
assert(empty('0') == true)
assert(empty('') == true)
assert(empty('false') == true)
assert(empty('a') == false)
assert(empty('true') == false)
assert(empty('0123') == false)
print('all test done')
