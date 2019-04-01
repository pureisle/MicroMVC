-- lua Framework/Tests/Luas/TestClass.lua
require "../../Framework/Luas/Class"
Geometry = Class:new('Geometry')
function Geometry:new(name)
    self.name = name
    return self
end
function Geometry:getName()
    return self.name
end
class_name1 = 'Geometry'
class_name2 = 'Rectangle'
g_obj = Geometry:new(class_name1)
assert(g_obj:getName() == class_name1)
assert(g_obj.class == class_name1)
assert(tostring(g_obj) == 'object')
assert(Class:type(g_obj) == class_name1)
Rectangle = Class:new('Rectangle', Geometry)
function Rectangle:new(x, y)
    self.x = x
    self.y = y
    self.parent:new(class_name2)
    return self
end
function Rectangle:area()
    return self.x * self.y
end

rt = Rectangle:new(5, 7)
assert(rt.class == class_name2)
assert(rt:getName() == class_name2)
assert(Class:type(rt) == class_name2)
assert(rt:area() == 35)
print('all test done')
