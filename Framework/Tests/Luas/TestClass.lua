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
class_name3 = 'Square'
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

Square = Class:new('Square', Rectangle)
function Square:new(x)
    self.x = x
    self.y = x
    self.parent:new(class_name3)
    return self
end
sq = Square:new(7)
assert(sq:area() == 49)

xpcall(function ()
    rt:aaaa()
end, function (msg)
    assert(msg == 'index: aaaa undefined')
end)
print('all test done')
