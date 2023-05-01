from Utility722 import Utility722
from Utility722 import UtilityBillType


class Bill722:
    @staticmethod
    def getDescription() -> str:
        return "Calculate utility bill for 722.\n"

    if __name__ == "__main__":
        utility = Utility722()
        print("The user name are:. Please select one.")
        print(utility.residents.keys())
        resident_names = list(utility.residents.keys())
        id = int(input())
        if id < 0 or id >= len(resident_names):
            print("Wrong selection! {}".format(id))
            exit
        name = resident_names[id]
        print("Below are utility bill for {}\n".format(name))
        elect = utility.get_total_bill_for_resident(name, UtilityBillType.Elec)
        water = utility.get_total_bill_for_resident(
            name, UtilityBillType.Water)
        print("\nElectricity:\n")
        print(elect)
        print("\Water:\n")
        print(water)
        print("\total:\n")
        print(elect+water)
