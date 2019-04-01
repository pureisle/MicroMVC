require "../../Luas/Class"
Geometry = Class:new('Geometry')
function Geometry:new(name)
    self.name = name
    return self
end
function Geometry:getName()
    return self.name
end
g_obj = Geometry:new('Geometry')
print(g_obj:getName(), "++")
print(g_obj.class)
print(g_obj)

Rectangle = Class:new('Rectangle', Geometry)
function Rectangle:new(x, y)
    self.x = x
    self.y = y
    self.parent:new('Rectangle')
    return self
end
function Rectangle:area()
    return self.x * self.y
end

rt = Rectangle:new(5, 7)
print(rt.class)
print(rt:getName(), ']]]]')
print(Class:type(g_obj))
print(rt:area())
